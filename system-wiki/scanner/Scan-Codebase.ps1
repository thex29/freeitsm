<#
.SYNOPSIS
    System Wiki - Codebase Scanner
    Scans the sdtickets codebase and populates the wiki database tables.

.DESCRIPTION
    Reads every PHP and JS file, extracts functions, classes, dependencies,
    database table references, session variables, and cross-references.
    Uses System.Data.SqlClient (built into .NET) - no extra modules needed.

.PARAMETER RootPath
    Root path of the codebase to scan.

.PARAMETER SqlServer
    SQL Server instance name.

.PARAMETER Database
    Database name.

.PARAMETER UseSqlAuth
    Use SQL authentication instead of Windows authentication.

.PARAMETER SqlUser
    SQL username (only used with -UseSqlAuth).

.PARAMETER SqlPassword
    SQL password (only used with -UseSqlAuth).
#>

param(
    [string]$RootPath = "\\bwbiis07\d$\httpdocs\sdtickets",
    [string]$SqlServer = "BWBSQL05\LIVE",
    [string]$Database = "SDTICKETS",
    [switch]$UseSqlAuth,
    [string]$SqlUser = "",
    [string]$SqlPassword = ""
)

# ─── Configuration ───────────────────────────────────────────────────────────

# Folders to exclude (relative to root, using backslash)
$ExcludeFolders = @(
    "assets\js\tinymce",
    "node_modules",
    ".git",
    "tickets\attachments"
)

# File extensions to scan
$IncludeExtensions = @("*.php", "*.js")

# SQL keywords to filter out of table name detection
$SqlKeywords = @(
    'SELECT','FROM','WHERE','SET','INTO','VALUES','UPDATE','DELETE','INSERT',
    'JOIN','LEFT','RIGHT','INNER','OUTER','FULL','CROSS','ON','AND','OR','NOT',
    'NULL','IS','IN','EXISTS','BETWEEN','LIKE','AS','ORDER','BY','GROUP','HAVING',
    'UNION','ALL','DISTINCT','TOP','CASE','WHEN','THEN','ELSE','END','CREATE',
    'TABLE','ALTER','DROP','INDEX','CONSTRAINT','PRIMARY','KEY','FOREIGN',
    'REFERENCES','DEFAULT','IDENTITY','INT','NVARCHAR','VARCHAR','BIT','DATETIME',
    'BIGINT','MAX','COUNT','SUM','AVG','MIN','GETDATE','DATEDIFF','CAST',
    'CONVERT','COALESCE','ISNULL','NEWID','LEN','REPLACE','SUBSTRING','TRIM',
    'LOWER','UPPER','BEGIN','COMMIT','ROLLBACK','TRANSACTION','OUTPUT','INSERTED',
    'DELETED','IF','ELSE','PRINT','EXEC','EXECUTE','DECLARE','FETCH','NEXT',
    'CURSOR','OPEN','CLOSE','DEALLOCATE','SCOPE_IDENTITY','GO','USE','NONCLUSTERED',
    'CLUSTERED','ASC','DESC','WITH','NOLOCK','ROWCOUNT','ROWS','ROW_NUMBER','OVER',
    'PARTITION','RANK','DENSE_RANK','LAG','LEAD','FIRST_VALUE','LAST_VALUE',
    'STUFF','STRING_AGG','FOR','XML','PATH','JSON','OPENJSON','CROSS_APPLY',
    'OUTER_APPLY','PIVOT','UNPIVOT','MERGE','USING','MATCHED','TARGET','SOURCE',
    'sysobjects','sys','xtype','information_schema','COLUMNS','TABLES','ROUTINES'
)

# ─── SQL Helper Functions ────────────────────────────────────────────────────

$connection = $null

function Connect-Database {
    if ($UseSqlAuth) {
        $connStr = "Server=$SqlServer;Database=$Database;User Id=$SqlUser;Password=$SqlPassword;"
    } else {
        $connStr = "Server=$SqlServer;Database=$Database;Trusted_Connection=Yes;"
    }
    $script:connection = New-Object System.Data.SqlClient.SqlConnection($connStr)
    $script:connection.Open()
    Write-Host "Connected to $SqlServer\$Database" -ForegroundColor Green
}

function Invoke-Sql {
    param(
        [string]$Query,
        [hashtable]$Parameters = @{}
    )
    $cmd = $connection.CreateCommand()
    $cmd.CommandText = $Query
    $cmd.CommandTimeout = 120
    foreach ($key in $Parameters.Keys) {
        $val = $Parameters[$key]
        if ($null -eq $val) {
            [void]$cmd.Parameters.AddWithValue("@$key", [DBNull]::Value)
        } else {
            [void]$cmd.Parameters.AddWithValue("@$key", $val)
        }
    }
    return $cmd.ExecuteNonQuery()
}

function Invoke-SqlScalar {
    param(
        [string]$Query,
        [hashtable]$Parameters = @{}
    )
    $cmd = $connection.CreateCommand()
    $cmd.CommandText = $Query
    $cmd.CommandTimeout = 120
    foreach ($key in $Parameters.Keys) {
        $val = $Parameters[$key]
        if ($null -eq $val) {
            [void]$cmd.Parameters.AddWithValue("@$key", [DBNull]::Value)
        } else {
            [void]$cmd.Parameters.AddWithValue("@$key", $val)
        }
    }
    return $cmd.ExecuteScalar()
}

function Invoke-SqlReader {
    param(
        [string]$Query,
        [hashtable]$Parameters = @{}
    )
    $cmd = $connection.CreateCommand()
    $cmd.CommandText = $Query
    $cmd.CommandTimeout = 120
    foreach ($key in $Parameters.Keys) {
        $val = $Parameters[$key]
        if ($null -eq $val) {
            [void]$cmd.Parameters.AddWithValue("@$key", [DBNull]::Value)
        } else {
            [void]$cmd.Parameters.AddWithValue("@$key", $val)
        }
    }
    $reader = $cmd.ExecuteReader()
    $results = @()
    while ($reader.Read()) {
        $row = @{}
        for ($i = 0; $i -lt $reader.FieldCount; $i++) {
            $row[$reader.GetName($i)] = $reader.GetValue($i)
        }
        $results += $row
    }
    $reader.Close()
    return $results
}

# ─── Extraction Helpers ──────────────────────────────────────────────────────

function Get-LineNumber {
    param([string]$Content, [int]$Position)
    $sub = $Content.Substring(0, [Math]::Min($Position, $Content.Length))
    return ($sub.Split("`n")).Count
}

function Get-PrecedingDocblock {
    param([string[]]$Lines, [int]$LineIndex)
    $desc = ""
    $inBlock = $false
    for ($i = $LineIndex - 1; $i -ge 0; $i--) {
        $trimmed = $Lines[$i].Trim()
        if ($trimmed -eq '') {
            if (-not $inBlock) { break }
        }
        if ($trimmed -match '^\*/$') { $inBlock = $true; continue }
        if ($trimmed -match '^\*\s*(.*)') {
            $desc = $Matches[1].Trim() + " " + $desc
            continue
        }
        if ($trimmed -match '^/\*\*(.*)') {
            $extra = $Matches[1].Trim()
            if ($extra -and $extra -ne '*') { $desc = $extra + " " + $desc }
            break
        }
        if ($inBlock) { continue }
        break
    }
    return $desc.Trim()
}

function Get-FileDocblock {
    param([string[]]$Lines)
    # Look for a docblock near the top of the file (within first 20 lines)
    $desc = ""
    $inBlock = $false
    $limit = [Math]::Min(20, $Lines.Count)
    for ($i = 0; $i -lt $limit; $i++) {
        $trimmed = $Lines[$i].Trim()
        if ($trimmed -match '^/\*\*') { $inBlock = $true; continue }
        if ($inBlock) {
            if ($trimmed -match '^\*/$') { break }
            if ($trimmed -match '^\*\s*(.+)') {
                $line = $Matches[1].Trim()
                # Skip @param, @return etc
                if ($line -notmatch '^@') {
                    $desc += $line + " "
                }
            }
        }
    }
    return $desc.Trim()
}

# ─── Main Scanner ────────────────────────────────────────────────────────────

try {
    $startTime = Get-Date
    Write-Host "========================================" -ForegroundColor Cyan
    Write-Host "  System Wiki - Codebase Scanner" -ForegroundColor Cyan
    Write-Host "  $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" -ForegroundColor Cyan
    Write-Host "========================================" -ForegroundColor Cyan
    Write-Host ""

    # Step 1: Connect and create scan run
    Connect-Database

    $scanId = Invoke-SqlScalar -Query "INSERT INTO wiki_scan_runs (status, scanned_by) OUTPUT INSERTED.id VALUES ('running', @host)" -Parameters @{ host = $env:COMPUTERNAME }
    Write-Host "Scan ID: $scanId" -ForegroundColor Yellow

    # Step 2: Delete old scan data (cascade clears everything)
    $oldScans = Invoke-Sql -Query "DELETE FROM wiki_scan_runs WHERE id != @id" -Parameters @{ id = $scanId }
    Write-Host "Cleared old scan data" -ForegroundColor DarkGray

    # Step 3: Collect files
    Write-Host ""
    Write-Host "Collecting files..." -ForegroundColor Cyan

    $allFiles = Get-ChildItem -Path $RootPath -Recurse -Include $IncludeExtensions -File | Where-Object {
        $filePath = $_.FullName
        $exclude = $false
        foreach ($folder in $ExcludeFolders) {
            if ($filePath -like "*\$folder\*" -or $filePath -like "*\$folder") {
                $exclude = $true
                break
            }
        }
        -not $exclude
    }

    $totalFiles = $allFiles.Count
    Write-Host "Found $totalFiles files to scan" -ForegroundColor Green
    Write-Host ""

    # Step 4: Process each file
    $fileIndex = 0
    $fileIdMap = @{}        # relativePath -> fileId
    $allFunctionNames = @() # collect all user-defined function names

    foreach ($file in $allFiles) {
        $fileIndex++
        $relativePath = $file.FullName.Substring($RootPath.Length).TrimStart('\').Replace('\', '/')
        $fileName = $file.Name
        $folderPath = if ($relativePath.Contains('/')) { $relativePath.Substring(0, $relativePath.LastIndexOf('/')) } else { '' }
        $fileType = if ($file.Extension -eq '.php') { 'PHP' } else { 'JS' }

        $pct = [math]::Round(($fileIndex / $totalFiles) * 100)
        Write-Host "[$pct%] $relativePath" -ForegroundColor DarkGray

        # Read file content
        try {
            $content = [System.IO.File]::ReadAllText($file.FullName)
        } catch {
            Write-Host "  WARN: Could not read file: $_" -ForegroundColor Yellow
            continue
        }

        $lines = $content -split "`n"
        $lineCount = $lines.Count

        # Get file description from docblock
        $fileDesc = Get-FileDocblock -Lines $lines
        if ($fileDesc.Length -gt 2000) { $fileDesc = $fileDesc.Substring(0, 2000) }

        # Insert file record
        $fileId = Invoke-SqlScalar -Query @"
            INSERT INTO wiki_files (scan_id, file_path, file_name, folder_path, file_type, file_size_bytes, line_count, last_modified, description)
            OUTPUT INSERTED.id
            VALUES (@scan_id, @file_path, @file_name, @folder_path, @file_type, @file_size, @line_count, @last_modified, @description)
"@ -Parameters @{
            scan_id = $scanId
            file_path = $relativePath
            file_name = $fileName
            folder_path = $folderPath
            file_type = $fileType
            file_size = $file.Length
            line_count = $lineCount
            last_modified = $file.LastWriteTime
            description = if ($fileDesc) { $fileDesc } else { $null }
        }

        $fileIdMap[$relativePath] = $fileId

        # ── Extract Functions ────────────────────────────────────────────
        if ($fileType -eq 'PHP') {
            # PHP functions: standalone and class methods
            $funcMatches = [regex]::Matches($content, '(?m)^[ \t]*((?:public|private|protected)\s+)?(static\s+)?function\s+(\w+)\s*\(([^)]*)\)')
            foreach ($match in $funcMatches) {
                $visibility = if ($match.Groups[1].Value.Trim()) { $match.Groups[1].Value.Trim() } else { $null }
                $isStatic = if ($match.Groups[2].Value.Trim()) { 1 } else { 0 }
                $funcName = $match.Groups[3].Value
                $params = $match.Groups[4].Value.Trim()
                $lineNum = Get-LineNumber -Content $content -Position $match.Index
                $funcDesc = Get-PrecedingDocblock -Lines $lines -LineIndex ($lineNum - 1)

                $allFunctionNames += $funcName

                Invoke-Sql -Query @"
                    INSERT INTO wiki_functions (file_id, function_name, line_number, parameters, visibility, is_static, description)
                    VALUES (@file_id, @name, @line, @params, @visibility, @is_static, @desc)
"@ -Parameters @{
                    file_id = $fileId
                    name = $funcName
                    line = $lineNum
                    params = if ($params) { $params } else { $null }
                    visibility = $visibility
                    is_static = $isStatic
                    desc = if ($funcDesc) { $funcDesc } else { $null }
                }
            }
        }

        if ($fileType -eq 'JS') {
            # JS function declarations
            $jsFuncMatches = [regex]::Matches($content, '(?m)^[ \t]*(async\s+)?function\s+(\w+)\s*\(([^)]*)\)')
            foreach ($match in $jsFuncMatches) {
                $funcName = $match.Groups[2].Value
                $params = $match.Groups[3].Value.Trim()
                $lineNum = Get-LineNumber -Content $content -Position $match.Index
                $funcDesc = Get-PrecedingDocblock -Lines $lines -LineIndex ($lineNum - 1)

                $allFunctionNames += $funcName

                Invoke-Sql -Query "INSERT INTO wiki_functions (file_id, function_name, line_number, parameters, description) VALUES (@file_id, @name, @line, @params, @desc)" -Parameters @{
                    file_id = $fileId; name = $funcName; line = $lineNum
                    params = if ($params) { $params } else { $null }
                    desc = if ($funcDesc) { $funcDesc } else { $null }
                }
            }

            # JS const/let/var function assignments
            $jsConstMatches = [regex]::Matches($content, '(?m)^[ \t]*(?:const|let|var)\s+(\w+)\s*=\s*(?:async\s+)?(?:function\s*)?\(([^)]*)\)\s*(?:=>|{)')
            foreach ($match in $jsConstMatches) {
                $funcName = $match.Groups[1].Value
                $params = $match.Groups[2].Value.Trim()
                $lineNum = Get-LineNumber -Content $content -Position $match.Index

                $allFunctionNames += $funcName

                Invoke-Sql -Query "INSERT INTO wiki_functions (file_id, function_name, line_number, parameters) VALUES (@file_id, @name, @line, @params)" -Parameters @{
                    file_id = $fileId; name = $funcName; line = $lineNum
                    params = if ($params) { $params } else { $null }
                }
            }
        }

        # ── Extract Classes (PHP only) ───────────────────────────────────
        if ($fileType -eq 'PHP') {
            $classMatches = [regex]::Matches($content, '(?m)^[ \t]*(?:abstract\s+)?class\s+(\w+)(?:\s+extends\s+(\w+))?(?:\s+implements\s+([\w,\s]+))?')
            foreach ($match in $classMatches) {
                $className = $match.Groups[1].Value
                $extends = if ($match.Groups[2].Value) { $match.Groups[2].Value } else { $null }
                $implements = if ($match.Groups[3].Value) { $match.Groups[3].Value.Trim() } else { $null }
                $lineNum = Get-LineNumber -Content $content -Position $match.Index
                $classDesc = Get-PrecedingDocblock -Lines $lines -LineIndex ($lineNum - 1)

                Invoke-Sql -Query "INSERT INTO wiki_classes (file_id, class_name, line_number, extends_class, implements_interfaces, description) VALUES (@file_id, @name, @line, @extends, @implements, @desc)" -Parameters @{
                    file_id = $fileId; name = $className; line = $lineNum
                    extends = $extends; implements = $implements
                    desc = if ($classDesc) { $classDesc } else { $null }
                }
            }
        }

        # ── Extract Dependencies ─────────────────────────────────────────
        # PHP includes/requires
        if ($fileType -eq 'PHP') {
            $includeRegex = '(?m)(require_once|require|include_once|include)\s+[''"]([^''"]+)[''"]'
            $includeMatches = [regex]::Matches($content, $includeRegex)
            foreach ($match in $includeMatches) {
                $depType = $match.Groups[1].Value
                $target = $match.Groups[2].Value
                $lineNum = Get-LineNumber -Content $content -Position $match.Index
                Invoke-Sql -Query "INSERT INTO wiki_dependencies (file_id, dependency_type, target_path, line_number) VALUES (@fid, @type, @target, @line)" -Parameters @{
                    fid = $fileId; type = $depType; target = $target; line = $lineNum
                }
            }

            # Variable-concatenated includes: require_once $path_prefix . 'includes/file.php'
            $concatRegex = '(?m)(require_once|require|include_once|include)\s+\$\w+\s*\.\s*[''"]([^''"]+)[''"]'
            $concatMatches = [regex]::Matches($content, $concatRegex)
            foreach ($match in $concatMatches) {
                $depType = $match.Groups[1].Value
                $target = $match.Groups[2].Value
                $lineNum = Get-LineNumber -Content $content -Position $match.Index
                Invoke-Sql -Query "INSERT INTO wiki_dependencies (file_id, dependency_type, target_path, line_number) VALUES (@fid, @type, @target, @line)" -Parameters @{
                    fid = $fileId; type = $depType; target = $target; line = $lineNum
                }
            }

            # PHP header redirects
            $redirectRegex = 'header\s*\(\s*[''"]Location:\s*([^''"]+)[''"]'
            $redirectMatches = [regex]::Matches($content, $redirectRegex)
            foreach ($match in $redirectMatches) {
                $target = $match.Groups[1].Value
                $lineNum = Get-LineNumber -Content $content -Position $match.Index
                Invoke-Sql -Query "INSERT INTO wiki_dependencies (file_id, dependency_type, target_path, line_number) VALUES (@fid, @type, @target, @line)" -Parameters @{
                    fid = $fileId; type = 'redirect'; target = $target; line = $lineNum
                }
            }
        }

        # JS fetch calls - match fetch('...') or fetch(API_BASE + '...')
        $fetchRegex = 'fetch\s*\(\s*(?:API_BASE\s*\+\s*)?[''"]([^''"]+)[''"]'
        $fetchMatches = [regex]::Matches($content, $fetchRegex)
        foreach ($match in $fetchMatches) {
            $target = $match.Groups[1].Value
            $lineNum = Get-LineNumber -Content $content -Position $match.Index
            Invoke-Sql -Query "INSERT INTO wiki_dependencies (file_id, dependency_type, target_path, line_number) VALUES (@fid, @type, @target, @line)" -Parameters @{
                fid = $fileId; type = 'fetch'; target = $target; line = $lineNum
            }
        }

        # Also match template literal fetch calls: fetch(`${API_BASE}file.php`) or fetch(API_BASE + `file.php`)
        $fetchTemplateRegex = 'fetch\s*\(\s*(?:API_BASE\s*\+\s*)?`([^`]+)`'
        $fetchTemplateMatches = [regex]::Matches($content, $fetchTemplateRegex)
        foreach ($match in $fetchTemplateMatches) {
            $target = $match.Groups[1].Value
            # Strip template expressions like ${...}
            $target = [regex]::Replace($target, '\$\{[^}]+\}', '')
            if ($target -eq '') { continue }
            $lineNum = Get-LineNumber -Content $content -Position $match.Index
            Invoke-Sql -Query "INSERT INTO wiki_dependencies (file_id, dependency_type, target_path, line_number) VALUES (@fid, @type, @target, @line)" -Parameters @{
                fid = $fileId; type = 'fetch'; target = $target; line = $lineNum
            }
        }

        # HTML href to PHP files
        $hrefRegex = 'href\s*=\s*[\"'']([^\"'']+\.php[^\"'']*)[\"'']'
        $hrefMatches = [regex]::Matches($content, $hrefRegex)
        foreach ($match in $hrefMatches) {
            $target = $match.Groups[1].Value
            # Skip PHP echo constructs
            if ($target -match '^\<\?php') { continue }
            $lineNum = Get-LineNumber -Content $content -Position $match.Index
            Invoke-Sql -Query "INSERT INTO wiki_dependencies (file_id, dependency_type, target_path, line_number) VALUES (@fid, @type, @target, @line)" -Parameters @{
                fid = $fileId; type = 'href'; target = $target; line = $lineNum
            }
        }

        # Form actions
        $formRegex = 'action\s*=\s*[\"'']([^\"'']+)[\"'']'
        $formMatches = [regex]::Matches($content, $formRegex)
        foreach ($match in $formMatches) {
            $target = $match.Groups[1].Value
            if ($target -eq '#' -or $target -eq '') { continue }
            $lineNum = Get-LineNumber -Content $content -Position $match.Index
            Invoke-Sql -Query "INSERT INTO wiki_dependencies (file_id, dependency_type, target_path, line_number) VALUES (@fid, @type, @target, @line)" -Parameters @{
                fid = $fileId; type = 'form_action'; target = $target; line = $lineNum
            }
        }

        # ── Extract Database Table References ────────────────────────────
        $sqlKeywordsLower = $SqlKeywords | ForEach-Object { $_.ToLower() }

        # FROM/JOIN
        $fromMatches = [regex]::Matches($content, '(?i)(?:FROM|JOIN)\s+(\w+)')
        foreach ($match in $fromMatches) {
            $tableName = $match.Groups[1].Value
            if ($sqlKeywordsLower -contains $tableName.ToLower()) { continue }
            if ($tableName -match '^\d') { continue }
            $refType = if ($match.Value -match '(?i)JOIN') { 'JOIN' } else { 'SELECT' }
            $lineNum = Get-LineNumber -Content $content -Position $match.Index
            Invoke-Sql -Query "INSERT INTO wiki_db_references (file_id, table_name, reference_type, line_number) VALUES (@fid, @table, @type, @line)" -Parameters @{
                fid = $fileId; table = $tableName; type = $refType; line = $lineNum
            }
        }

        # INSERT INTO
        $insertMatches = [regex]::Matches($content, '(?i)INSERT\s+INTO\s+(\w+)')
        foreach ($match in $insertMatches) {
            $tableName = $match.Groups[1].Value
            if ($sqlKeywordsLower -contains $tableName.ToLower()) { continue }
            $lineNum = Get-LineNumber -Content $content -Position $match.Index
            Invoke-Sql -Query "INSERT INTO wiki_db_references (file_id, table_name, reference_type, line_number) VALUES (@fid, @table, @type, @line)" -Parameters @{
                fid = $fileId; table = $tableName; type = 'INSERT'; line = $lineNum
            }
        }

        # UPDATE ... SET
        $updateMatches = [regex]::Matches($content, '(?i)UPDATE\s+(\w+)\s+SET')
        foreach ($match in $updateMatches) {
            $tableName = $match.Groups[1].Value
            if ($sqlKeywordsLower -contains $tableName.ToLower()) { continue }
            $lineNum = Get-LineNumber -Content $content -Position $match.Index
            Invoke-Sql -Query "INSERT INTO wiki_db_references (file_id, table_name, reference_type, line_number) VALUES (@fid, @table, @type, @line)" -Parameters @{
                fid = $fileId; table = $tableName; type = 'UPDATE'; line = $lineNum
            }
        }

        # DELETE FROM
        $deleteMatches = [regex]::Matches($content, '(?i)DELETE\s+(?:FROM\s+)?(\w+)\s+WHERE')
        foreach ($match in $deleteMatches) {
            $tableName = $match.Groups[1].Value
            if ($sqlKeywordsLower -contains $tableName.ToLower()) { continue }
            $lineNum = Get-LineNumber -Content $content -Position $match.Index
            Invoke-Sql -Query "INSERT INTO wiki_db_references (file_id, table_name, reference_type, line_number) VALUES (@fid, @table, @type, @line)" -Parameters @{
                fid = $fileId; table = $tableName; type = 'DELETE'; line = $lineNum
            }
        }

        # CREATE TABLE
        $createMatches = [regex]::Matches($content, '(?i)CREATE\s+TABLE\s+(\w+)')
        foreach ($match in $createMatches) {
            $tableName = $match.Groups[1].Value
            if ($sqlKeywordsLower -contains $tableName.ToLower()) { continue }
            $lineNum = Get-LineNumber -Content $content -Position $match.Index
            Invoke-Sql -Query "INSERT INTO wiki_db_references (file_id, table_name, reference_type, line_number) VALUES (@fid, @table, @type, @line)" -Parameters @{
                fid = $fileId; table = $tableName; type = 'CREATE'; line = $lineNum
            }
        }

        # ── Extract Session Variables (PHP only) ─────────────────────────
        if ($fileType -eq 'PHP') {
            # Find all $_SESSION accesses
            $sessionMatches = [regex]::Matches($content, "\`$_SESSION\s*\[\s*['""](\w+)['""]\s*\]")
            foreach ($match in $sessionMatches) {
                $varName = $match.Groups[1].Value
                $lineNum = Get-LineNumber -Content $content -Position $match.Index

                # Check if this is a write (has = after, but not == or !=)
                $afterMatch = $content.Substring($match.Index + $match.Length)
                $accessType = if ($afterMatch -match '^\s*=[^=]') { 'write' } else { 'read' }

                Invoke-Sql -Query "INSERT INTO wiki_session_vars (file_id, variable_name, access_type, line_number) VALUES (@fid, @name, @type, @line)" -Parameters @{
                    fid = $fileId; name = $varName; type = $accessType; line = $lineNum
                }
            }
        }
    }

    # ── Step 5: Resolve dependency links ─────────────────────────────────
    Write-Host ""
    Write-Host "Resolving dependency links..." -ForegroundColor Cyan

    # For each unresolved dependency, try to match it to a file
    $unresolvedDeps = Invoke-SqlReader -Query @"
        SELECT d.id, d.target_path, d.file_id, f.folder_path
        FROM wiki_dependencies d
        INNER JOIN wiki_files f ON d.file_id = f.id
        WHERE d.resolved_file_id IS NULL AND f.scan_id = @scan_id
"@ -Parameters @{ scan_id = $scanId }

    $resolvedCount = 0
    foreach ($dep in $unresolvedDeps) {
        $target = $dep['target_path']
        $sourceFolder = $dep['folder_path']

        # Try to resolve relative path from source file's folder
        # Strip ../ prefixes and build path
        $cleanTarget = $target -replace '^\.\.\/', ''
        while ($cleanTarget -match '^\.\.\/' -and $sourceFolder -match '/') {
            $cleanTarget = $cleanTarget -replace '^\.\.\/', ''
            $sourceFolder = $sourceFolder -replace '/[^/]+$', ''
        }

        # Try exact match first
        $resolvedId = Invoke-SqlScalar -Query "SELECT TOP 1 id FROM wiki_files WHERE scan_id = @scan_id AND file_path = @path" -Parameters @{
            scan_id = $scanId; path = $cleanTarget
        }

        # Try suffix match
        if (-not $resolvedId) {
            $resolvedId = Invoke-SqlScalar -Query "SELECT TOP 1 id FROM wiki_files WHERE scan_id = @scan_id AND file_path LIKE @pattern" -Parameters @{
                scan_id = $scanId; pattern = "%/$cleanTarget"
            }
        }

        # Try filename only match
        if (-not $resolvedId) {
            $justFile = $target.Split('/')[-1]
            if ($justFile -match '\.\w+$') {
                $resolvedId = Invoke-SqlScalar -Query "SELECT TOP 1 id FROM wiki_files WHERE scan_id = @scan_id AND file_name = @name" -Parameters @{
                    scan_id = $scanId; name = $justFile
                }
            }
        }

        if ($resolvedId) {
            Invoke-Sql -Query "UPDATE wiki_dependencies SET resolved_file_id = @rid WHERE id = @id" -Parameters @{
                rid = $resolvedId; id = $dep['id']
            }
            $resolvedCount++
        }
    }

    Write-Host "Resolved $resolvedCount of $($unresolvedDeps.Count) dependencies" -ForegroundColor Green

    # ── Step 6: Cross-reference function calls ───────────────────────────
    Write-Host ""
    Write-Host "Cross-referencing function calls..." -ForegroundColor Cyan

    # Get unique user-defined function names
    $uniqueFunctions = $allFunctionNames | Select-Object -Unique | Where-Object { $_.Length -gt 2 }
    Write-Host "Tracking $($uniqueFunctions.Count) unique function names" -ForegroundColor DarkGray

    $callCount = 0
    foreach ($file in $allFiles) {
        $relativePath = $file.FullName.Substring($RootPath.Length).TrimStart('\').Replace('\', '/')
        $fileId = $fileIdMap[$relativePath]
        if (-not $fileId) { continue }

        try {
            $content = [System.IO.File]::ReadAllText($file.FullName)
        } catch {
            continue
        }

        foreach ($funcName in $uniqueFunctions) {
            # Search for function calls: funcName( but not function funcName(
            $callMatches = [regex]::Matches($content, "(?<!function\s)(?<!\w)$([regex]::Escape($funcName))\s*\(")
            foreach ($match in $callMatches) {
                $lineNum = Get-LineNumber -Content $content -Position $match.Index
                Invoke-Sql -Query "INSERT INTO wiki_function_calls (file_id, function_name, line_number) VALUES (@fid, @name, @line)" -Parameters @{
                    fid = $fileId; name = $funcName; line = $lineNum
                }
                $callCount++
            }
        }
    }

    Write-Host "Found $callCount function call references" -ForegroundColor Green

    # ── Step 7: Finalize scan ────────────────────────────────────────────
    Write-Host ""
    Write-Host "Finalizing scan..." -ForegroundColor Cyan

    Invoke-Sql -Query @"
        UPDATE wiki_scan_runs
        SET completed_at = GETDATE(),
            status = 'completed',
            files_scanned = (SELECT COUNT(*) FROM wiki_files WHERE scan_id = @id),
            functions_found = (SELECT COUNT(*) FROM wiki_functions fn INNER JOIN wiki_files f ON fn.file_id = f.id WHERE f.scan_id = @id),
            classes_found = (SELECT COUNT(*) FROM wiki_classes c INNER JOIN wiki_files f ON c.file_id = f.id WHERE f.scan_id = @id)
        WHERE id = @id
"@ -Parameters @{ id = $scanId }

    $elapsed = (Get-Date) - $startTime
    Write-Host ""
    Write-Host "========================================" -ForegroundColor Green
    Write-Host "  Scan Complete!" -ForegroundColor Green
    Write-Host "  Files: $totalFiles" -ForegroundColor Green
    Write-Host "  Functions: $($allFunctionNames.Count)" -ForegroundColor Green
    Write-Host "  Function calls: $callCount" -ForegroundColor Green
    Write-Host "  Dependencies resolved: $resolvedCount/$($unresolvedDeps.Count)" -ForegroundColor Green
    Write-Host "  Duration: $($elapsed.ToString('mm\:ss'))" -ForegroundColor Green
    Write-Host "========================================" -ForegroundColor Green

} catch {
    Write-Host ""
    Write-Host "ERROR: $_" -ForegroundColor Red
    Write-Host $_.ScriptStackTrace -ForegroundColor Red

    if ($scanId) {
        try {
            Invoke-Sql -Query "UPDATE wiki_scan_runs SET completed_at = GETDATE(), status = 'failed', error_message = @msg WHERE id = @id" -Parameters @{
                id = $scanId; msg = $_.ToString()
            }
        } catch {}
    }
} finally {
    if ($connection -and $connection.State -eq 'Open') {
        $connection.Close()
        Write-Host "Database connection closed." -ForegroundColor DarkGray
    }
}
