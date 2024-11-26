#
# Table structure for table 'tx_errorlog_domain_model_error'
#
CREATE TABLE tx_errorlog_domain_model_error (
	uid int(11) NOT NULL auto_increment,
	uri varchar(1000) NOT NULL,
	crdate int(15) NOT NULL,
	tstamp int(15) NOT NULL,
	PRIMARY KEY (uid)
);
