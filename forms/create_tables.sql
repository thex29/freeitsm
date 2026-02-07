-- Forms Module - Database Tables
-- Run this script against the SDTICKETS database

IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='forms' AND xtype='U')
BEGIN
    CREATE TABLE forms (
        id INT IDENTITY(1,1) PRIMARY KEY,
        title NVARCHAR(255) NOT NULL,
        description NVARCHAR(MAX),
        is_active BIT NOT NULL DEFAULT 1,
        created_by INT,
        created_date DATETIME NOT NULL DEFAULT GETDATE(),
        modified_date DATETIME NOT NULL DEFAULT GETDATE()
    );
    PRINT 'Created forms table';
END

IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='form_fields' AND xtype='U')
BEGIN
    CREATE TABLE form_fields (
        id INT IDENTITY(1,1) PRIMARY KEY,
        form_id INT NOT NULL,
        field_type NVARCHAR(50) NOT NULL,
        label NVARCHAR(255) NOT NULL,
        options NVARCHAR(MAX),
        is_required BIT NOT NULL DEFAULT 0,
        sort_order INT NOT NULL DEFAULT 0,
        CONSTRAINT FK_form_fields_form FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE
    );
    PRINT 'Created form_fields table';
END

IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='form_submissions' AND xtype='U')
BEGIN
    CREATE TABLE form_submissions (
        id INT IDENTITY(1,1) PRIMARY KEY,
        form_id INT NOT NULL,
        submitted_by INT,
        submitted_date DATETIME NOT NULL DEFAULT GETDATE(),
        CONSTRAINT FK_form_submissions_form FOREIGN KEY (form_id) REFERENCES forms(id)
    );
    PRINT 'Created form_submissions table';
END

IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='form_submission_data' AND xtype='U')
BEGIN
    CREATE TABLE form_submission_data (
        id INT IDENTITY(1,1) PRIMARY KEY,
        submission_id INT NOT NULL,
        field_id INT NOT NULL,
        field_value NVARCHAR(MAX),
        CONSTRAINT FK_submission_data_submission FOREIGN KEY (submission_id) REFERENCES form_submissions(id) ON DELETE CASCADE,
        CONSTRAINT FK_submission_data_field FOREIGN KEY (field_id) REFERENCES form_fields(id)
    );
    PRINT 'Created form_submission_data table';
END

-- Indexes
IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_form_fields_form_id')
    CREATE INDEX IX_form_fields_form_id ON form_fields(form_id);

IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_form_submissions_form_id')
    CREATE INDEX IX_form_submissions_form_id ON form_submissions(form_id);

IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_form_submission_data_submission_id')
    CREATE INDEX IX_form_submission_data_submission_id ON form_submission_data(submission_id);
