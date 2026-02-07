-- System Wiki Module - Database Tables
-- Run this script against the SDTICKETS database

-- Scan run history
IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='wiki_scan_runs' AND xtype='U')
BEGIN
    CREATE TABLE wiki_scan_runs (
        id INT IDENTITY(1,1) PRIMARY KEY,
        started_at DATETIME NOT NULL DEFAULT GETDATE(),
        completed_at DATETIME NULL,
        status NVARCHAR(20) NOT NULL DEFAULT 'running',
        files_scanned INT NOT NULL DEFAULT 0,
        functions_found INT NOT NULL DEFAULT 0,
        classes_found INT NOT NULL DEFAULT 0,
        error_message NVARCHAR(MAX) NULL,
        scanned_by NVARCHAR(100) NULL
    );
    PRINT 'Created wiki_scan_runs table';
END

-- Files catalogue
IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='wiki_files' AND xtype='U')
BEGIN
    CREATE TABLE wiki_files (
        id INT IDENTITY(1,1) PRIMARY KEY,
        scan_id INT NOT NULL,
        file_path NVARCHAR(500) NOT NULL,
        file_name NVARCHAR(255) NOT NULL,
        folder_path NVARCHAR(500) NOT NULL,
        file_type NVARCHAR(10) NOT NULL,
        file_size_bytes BIGINT NOT NULL DEFAULT 0,
        line_count INT NOT NULL DEFAULT 0,
        last_modified DATETIME NULL,
        description NVARCHAR(MAX) NULL,
        created_date DATETIME NOT NULL DEFAULT GETDATE(),
        CONSTRAINT FK_wiki_files_scan FOREIGN KEY (scan_id) REFERENCES wiki_scan_runs(id) ON DELETE CASCADE
    );
    PRINT 'Created wiki_files table';
END

-- Functions defined in files
IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='wiki_functions' AND xtype='U')
BEGIN
    CREATE TABLE wiki_functions (
        id INT IDENTITY(1,1) PRIMARY KEY,
        file_id INT NOT NULL,
        function_name NVARCHAR(255) NOT NULL,
        line_number INT NOT NULL,
        end_line_number INT NULL,
        parameters NVARCHAR(MAX) NULL,
        class_name NVARCHAR(255) NULL,
        visibility NVARCHAR(20) NULL,
        is_static BIT NOT NULL DEFAULT 0,
        description NVARCHAR(MAX) NULL,
        CONSTRAINT FK_wiki_functions_file FOREIGN KEY (file_id) REFERENCES wiki_files(id) ON DELETE CASCADE
    );
    PRINT 'Created wiki_functions table';
END

-- Classes defined in files
IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='wiki_classes' AND xtype='U')
BEGIN
    CREATE TABLE wiki_classes (
        id INT IDENTITY(1,1) PRIMARY KEY,
        file_id INT NOT NULL,
        class_name NVARCHAR(255) NOT NULL,
        line_number INT NOT NULL,
        extends_class NVARCHAR(255) NULL,
        implements_interfaces NVARCHAR(MAX) NULL,
        description NVARCHAR(MAX) NULL,
        CONSTRAINT FK_wiki_classes_file FOREIGN KEY (file_id) REFERENCES wiki_files(id) ON DELETE CASCADE
    );
    PRINT 'Created wiki_classes table';
END

-- File-to-file dependencies (includes, fetches, links)
IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='wiki_dependencies' AND xtype='U')
BEGIN
    CREATE TABLE wiki_dependencies (
        id INT IDENTITY(1,1) PRIMARY KEY,
        file_id INT NOT NULL,
        dependency_type NVARCHAR(50) NOT NULL,
        target_path NVARCHAR(500) NOT NULL,
        resolved_file_id INT NULL,
        line_number INT NULL,
        CONSTRAINT FK_wiki_deps_file FOREIGN KEY (file_id) REFERENCES wiki_files(id) ON DELETE CASCADE
    );
    PRINT 'Created wiki_dependencies table';
END

-- Database table references found in files
IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='wiki_db_references' AND xtype='U')
BEGIN
    CREATE TABLE wiki_db_references (
        id INT IDENTITY(1,1) PRIMARY KEY,
        file_id INT NOT NULL,
        table_name NVARCHAR(255) NOT NULL,
        reference_type NVARCHAR(50) NOT NULL,
        line_number INT NULL,
        CONSTRAINT FK_wiki_dbrefs_file FOREIGN KEY (file_id) REFERENCES wiki_files(id) ON DELETE CASCADE
    );
    PRINT 'Created wiki_db_references table';
END

-- Session variables used in files
IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='wiki_session_vars' AND xtype='U')
BEGIN
    CREATE TABLE wiki_session_vars (
        id INT IDENTITY(1,1) PRIMARY KEY,
        file_id INT NOT NULL,
        variable_name NVARCHAR(255) NOT NULL,
        access_type NVARCHAR(10) NOT NULL,
        line_number INT NULL,
        CONSTRAINT FK_wiki_sessvars_file FOREIGN KEY (file_id) REFERENCES wiki_files(id) ON DELETE CASCADE
    );
    PRINT 'Created wiki_session_vars table';
END

-- Function call cross-references
IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='wiki_function_calls' AND xtype='U')
BEGIN
    CREATE TABLE wiki_function_calls (
        id INT IDENTITY(1,1) PRIMARY KEY,
        file_id INT NOT NULL,
        function_name NVARCHAR(255) NOT NULL,
        line_number INT NULL,
        CONSTRAINT FK_wiki_funccalls_file FOREIGN KEY (file_id) REFERENCES wiki_files(id) ON DELETE CASCADE
    );
    PRINT 'Created wiki_function_calls table';
END

-- Indexes
IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_wiki_files_scan_id')
    CREATE INDEX IX_wiki_files_scan_id ON wiki_files(scan_id);

IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_wiki_files_folder_path')
    CREATE INDEX IX_wiki_files_folder_path ON wiki_files(folder_path);

IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_wiki_files_file_type')
    CREATE INDEX IX_wiki_files_file_type ON wiki_files(file_type);

IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_wiki_functions_file_id')
    CREATE INDEX IX_wiki_functions_file_id ON wiki_functions(file_id);

IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_wiki_functions_name')
    CREATE INDEX IX_wiki_functions_name ON wiki_functions(function_name);

IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_wiki_classes_file_id')
    CREATE INDEX IX_wiki_classes_file_id ON wiki_classes(file_id);

IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_wiki_deps_file_id')
    CREATE INDEX IX_wiki_deps_file_id ON wiki_dependencies(file_id);

IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_wiki_deps_resolved')
    CREATE INDEX IX_wiki_deps_resolved ON wiki_dependencies(resolved_file_id);

IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_wiki_dbrefs_file_id')
    CREATE INDEX IX_wiki_dbrefs_file_id ON wiki_db_references(file_id);

IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_wiki_dbrefs_table_name')
    CREATE INDEX IX_wiki_dbrefs_table_name ON wiki_db_references(table_name);

IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_wiki_sessvars_file_id')
    CREATE INDEX IX_wiki_sessvars_file_id ON wiki_session_vars(file_id);

IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_wiki_funccalls_file_id')
    CREATE INDEX IX_wiki_funccalls_file_id ON wiki_function_calls(file_id);

IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_wiki_funccalls_name')
    CREATE INDEX IX_wiki_funccalls_name ON wiki_function_calls(function_name);

PRINT 'All wiki tables and indexes created successfully';
