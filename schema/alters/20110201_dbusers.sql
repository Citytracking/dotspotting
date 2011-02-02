ALTER TABLE Sheets ADD simplified TINYINT(3) NOT NULL;
ALTER TABLE SheetsLookup ADD fingerprint CHAR(32) NOT NULL;

CREATE INDEX by_fingerprint ON SheetsLookup (fingerprint, user_id);
