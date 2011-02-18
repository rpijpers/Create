CREATE TABLE IF NOT EXISTS `#__create_components` (
  `create_component_id` SERIAL,
  `name` varchar(50) NOT NULL,
  `itemsname` varchar(50) NOT NULL,
  `itemname` varchar(50) NOT NULL,
  `filename` varchar(255) NOT NULL,
  PRIMARY KEY (`create_component_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=UTF8;