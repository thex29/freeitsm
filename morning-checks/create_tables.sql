-- =============================================
-- Morning Checks Database Schema
-- MS SQL Server
-- =============================================

-- Table for check definitions
CREATE TABLE morningChecks_Checks (
    CheckID INT IDENTITY(1,1) PRIMARY KEY,
    CheckName NVARCHAR(255) NOT NULL,
    CheckDescription NVARCHAR(MAX),
    IsActive BIT NOT NULL DEFAULT 1,
    SortOrder INT NOT NULL DEFAULT 0,
    CreatedDate DATETIME NOT NULL DEFAULT GETDATE(),
    ModifiedDate DATETIME NOT NULL DEFAULT GETDATE()
);

-- Table for daily check results
CREATE TABLE morningChecks_Results (
    ResultID INT IDENTITY(1,1) PRIMARY KEY,
    CheckID INT NOT NULL,
    CheckDate DATE NOT NULL,
    Status NVARCHAR(10) NOT NULL CHECK (Status IN ('Red', 'Amber', 'Green')),
    Notes NVARCHAR(MAX),
    CreatedBy NVARCHAR(100),
    CreatedDate DATETIME NOT NULL DEFAULT GETDATE(),
    ModifiedDate DATETIME NOT NULL DEFAULT GETDATE(),
    CONSTRAINT FK_Results_Checks FOREIGN KEY (CheckID) REFERENCES morningChecks_Checks(CheckID),
    CONSTRAINT UQ_CheckDate UNIQUE (CheckID, CheckDate)
);

-- Index for faster date-based queries (for the 30-day chart)
CREATE INDEX IX_Results_CheckDate ON morningChecks_Results(CheckDate DESC);

-- Index for faster check lookups
CREATE INDEX IX_Results_CheckID ON morningChecks_Results(CheckID);

GO

-- =============================================
-- Sample data (optional - remove if not needed)
-- =============================================

-- Insert some example checks
INSERT INTO morningChecks_Checks (CheckName, CheckDescription, SortOrder) VALUES
('Server Health', 'Check all production servers are responding and CPU/Memory within normal range', 1),
('Backup Status', 'Verify all scheduled backups completed successfully overnight', 2),
('Disk Space', 'Check disk space on all servers - alert if any drive >80% full', 3),
('Security Alerts', 'Review security logs and IDS/IPS alerts from past 24 hours', 4),
('Service Status', 'Verify all critical services and applications are running', 5),
('Network Performance', 'Check network latency and bandwidth utilization', 6);

GO
