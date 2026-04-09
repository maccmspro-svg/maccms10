-- 商城扩展：虚拟/实体、收货地址、发货字段（手动执行用）
-- 若表前缀不是 mac_，请全局替换 mac_ 为你的前缀后再执行。
-- 执行前请备份数据库。已存在的字段会报错，可忽略对应语句或先检查表结构。

ALTER TABLE `mac_goods` ADD `goods_kind` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '1虚拟2实体' AFTER `goods_group_ids`;
ALTER TABLE `mac_goods` ADD `goods_download` mediumtext COMMENT '虚拟商品付款后展示的下载/卡密等' AFTER `goods_kind`;

ALTER TABLE `mac_goods_order` ADD `go_goods_kind` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '1虚拟2实体' AFTER `go_remark`;
ALTER TABLE `mac_goods_order` ADD `go_ship_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0待发货1已发货' AFTER `go_goods_kind`;
ALTER TABLE `mac_goods_order` ADD `go_ship_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '发货时间' AFTER `go_ship_status`;
ALTER TABLE `mac_goods_order` ADD `go_express_name` varchar(64) NOT NULL DEFAULT '' COMMENT '快递公司' AFTER `go_ship_time`;
ALTER TABLE `mac_goods_order` ADD `go_express_no` varchar(128) NOT NULL DEFAULT '' COMMENT '运单号' AFTER `go_express_name`;
ALTER TABLE `mac_goods_order` ADD `go_address_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '收货地址ID' AFTER `go_express_no`;
ALTER TABLE `mac_goods_order` ADD `go_receiver_name` varchar(64) NOT NULL DEFAULT '' COMMENT '收货人' AFTER `go_address_id`;
ALTER TABLE `mac_goods_order` ADD `go_receiver_phone` varchar(32) NOT NULL DEFAULT '' COMMENT '收货手机' AFTER `go_receiver_name`;
ALTER TABLE `mac_goods_order` ADD `go_receiver_region` varchar(128) NOT NULL DEFAULT '' COMMENT '省市区' AFTER `go_receiver_phone`;
ALTER TABLE `mac_goods_order` ADD `go_receiver_address` varchar(512) NOT NULL DEFAULT '' COMMENT '详细地址' AFTER `go_receiver_region`;
ALTER TABLE `mac_goods_order` ADD `go_download_snapshot` mediumtext COMMENT '虚拟商品发货内容快照' AFTER `go_receiver_address`;

CREATE TABLE IF NOT EXISTS `mac_user_address` (
  `ua_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `ua_name` varchar(64) NOT NULL DEFAULT '' COMMENT '收货人',
  `ua_phone` varchar(32) NOT NULL DEFAULT '' COMMENT '手机',
  `ua_region` varchar(128) NOT NULL DEFAULT '' COMMENT '省市区',
  `ua_address` varchar(512) NOT NULL DEFAULT '' COMMENT '详细地址',
  `ua_is_default` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `ua_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ua_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='用户收货地址';

-- 商品收藏使用 mac_ulog（与影视一致），不创建 mac_goods_favorite
