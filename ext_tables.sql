#
# Table structure for table 'tx_sfjoin_domain_model_product'
#
CREATE TABLE tx_sfjoin_domain_model_product
(
	title      varchar(25) DEFAULT '' NOT NULL,
	categories int(11) DEFAULT '0' NOT NULL,
	properties int (11) DEFAULT '0' NOT NULL
);

#
# Table structure for table 'tx_sfjoin_domain_model_property'
#
CREATE TABLE tx_sfjoin_domain_model_property
(
	name    varchar(25) DEFAULT '' NOT NULL,
	value   varchar(25) DEFAULT '' NOT NULL,
	product int (11) DEFAULT '0' NOT NULL
);
