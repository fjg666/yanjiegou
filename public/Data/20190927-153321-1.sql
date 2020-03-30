
-- -----------------------------
-- Table structure for `shy_ad`
-- -----------------------------
DROP TABLE IF EXISTS `shy_ad`;
CREATE TABLE `shy_ad` (
  `ad_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '广告名称',
  `type_id` tinyint(5) NOT NULL COMMENT '所属位置',
  `pic` varchar(200) NOT NULL DEFAULT '' COMMENT '广告图片URL',
  `url` varchar(200) NOT NULL DEFAULT '' COMMENT '广告链接',
  `addtime` int(11) NOT NULL COMMENT '添加时间',
  `sort` int(11) NOT NULL COMMENT '排序',
  `open` tinyint(2) NOT NULL COMMENT '1=审核  0=未审核',
  `content` varchar(225) DEFAULT '' COMMENT '广告内容',
  `slideshow` tinyint(2) DEFAULT '0' COMMENT '轮播图',
  PRIMARY KEY (`ad_id`),
  KEY `plug_ad_adtypeid` (`type_id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 COMMENT='广告表';

-- -----------------------------
-- Records of `shy_ad`
-- -----------------------------
INSERT INTO `shy_ad` VALUES ('5', '1', '1', '/uploads/ad/201907/20190702162600885460.png', 'http://www.yanjiegou.com', '1562055983', '1', '1', '1', '0');
INSERT INTO `shy_ad` VALUES ('6', '2', '1', '/uploads/ad/201907/20190702162640214323.png', 'http://www.yanjiegou.com', '1562056007', '2', '1', '2', '0');
INSERT INTO `shy_ad` VALUES ('7', '3', '1', '/uploads/ad/201907/20190702162657416118.png', 'http://www.yanjiegou.com', '1562056027', '3', '1', '3', '0');
