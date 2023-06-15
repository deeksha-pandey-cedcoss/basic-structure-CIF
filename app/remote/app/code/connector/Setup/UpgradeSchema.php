<?php

namespace App\Connector\Setup;

use \Phalcon\Di\DiInterface;

class UpgradeSchema extends \App\Core\Setup\Schema
{
    
    public function up(DiInterface $di, $moduleName, $currentVersion)
    {
        $this->applyOnSingle($di, $moduleName, $currentVersion, 'db', function ($connection, $dbVersion) use ($di) {
            if ($dbVersion < '1.0.0') {
                /*
                $connection->query("
            		CREATE TABLE `user_connector` (
					   `id` int(11) NOT NULL,
					   `user_id` int(11) NOT NULL,
					   `code` varchar(50) NOT NULL,
					   `installed_at` datetime NOT NULL DEFAULT current_timestamp(),
					   `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
					) ENGINE=InnoDB DEFAULT CHARSET=utf8;
			        ALTER TABLE `user_connector` ADD PRIMARY KEY (`id`);
			        ALTER TABLE `user_connector` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
			        COMMIT;

			        INSERT INTO `core_config_data` (`framework`, `path`, `value`) VALUES ('global', 'enable_warehouse_feature', '0');

                    CREATE TABLE `warehouse` (
					   `id` int(11) NOT NULL COMMENT 'Id',
					   `merchant_id` int(11) NOT NULL COMMENT 'Merchant Id',
					   `name` varchar(255) NOT NULL COMMENT 'Name',
					   `status` int(1) DEFAULT NULL COMMENT 'Status',
					   `order_target` varchar(100) DEFAULT NULL,
					   `order_target_shop` varchar(150) DEFAULT NULL,
					   `country` varchar(5) DEFAULT NULL COMMENT 'Country',
					   `state` varchar(30) DEFAULT NULL COMMENT 'State',
					   `city` varchar(30) DEFAULT NULL COMMENT 'City',
					   `region` varchar(30) DEFAULT NULL COMMENT 'Region',
					   `street` varchar(255) DEFAULT NULL COMMENT 'Street',
					   `zipcode` varchar(15) DEFAULT NULL COMMENT 'Zipcode',
					   `longitude` varchar(15) DEFAULT NULL COMMENT 'Longitude',
					   `latitude` varchar(15) DEFAULT NULL COMMENT 'Latitude',
					   `handler` varchar(255) NOT NULL COMMENT 'Warehouse Handler'
					) ENGINE=InnoDB DEFAULT CHARSET=utf8;
					ALTER TABLE `warehouse`
					   ADD PRIMARY KEY (`id`),
					   ADD KEY `warehouses_merchant_ibfk_2` (`merchant_id`);
					ALTER TABLE `warehouse` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Id';
					COMMIT;

                    CREATE TABLE `product_attribute` (
                        `id` int(11) NOT NULL,
                        `label` varchar(255) NOT NULL,
                        `frontend_type` varchar(255) NOT NULL,
                        `code` varchar(255) NOT NULL,
                        `is_config` tinyint(1) NOT NULL DEFAULT 0,
                        `is_required` tinyint(1) NOT NULL DEFAULT 0,
                        `is_unique` tinyint(1) NOT NULL DEFAULT 0,
                        `apply_on` varchar(255) NOT NULL,
                        `merchant_id` int(11) NOT NULL,
                        `source_model` varchar(255) DEFAULT NULL,
                        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                        CONSTRAINT `product_attribute_merchant_id_fk` FOREIGN KEY (`merchant_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
                    ALTER TABLE `product_attribute` ADD PRIMARY KEY (`id`);
                    ALTER TABLE `product_attribute` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
                    COMMIT;

                    CREATE TABLE `product_attribute_option` (
                        `id` int(11) NOT NULL,
                        `attribute_id` int(11) NOT NULL,
                        `value` varchar(255) NOT NULL,
                        CONSTRAINT `product_attribute_option_attribute_id_fk` FOREIGN KEY (`attribute_id`) REFERENCES `product_attribute` (`id`) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
                    ALTER TABLE `product_attribute_option` ADD PRIMARY KEY (`id`);
                    ALTER TABLE `product_attribute_option` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
                    COMMIT;

                    CREATE TABLE `profile` (
                        `id` int(11) NOT NULL,
                        `name` varchar(255) NOT NULL,
                        `merchant_id` int(11) NOT NULL,
                        `code` varchar(50) DEFAULT NULL,
                        `qty_from_warehouse` varchar(50) NOT NULL,
                        `target` varchar(100) NOT NULL,
                        `shop` varchar(100) NOT NULL,
                        `category` varchar(100) DEFAULT NULL,
                        `attribute_mapping` text DEFAULT NULL,
                        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                        `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
                        CONSTRAINT `profile_merchant_id_fk` FOREIGN KEY (`merchant_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
                    ALTER TABLE `profile`
                        ADD PRIMARY KEY (`id`),
                        ADD KEY `profile_merchant_ibfk_2` (`merchant_id`);
                    ALTER TABLE `profile` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
                    COMMIT;

                    CREATE TABLE `profile_product` (
                        `id` int(11) NOT NULL,
                        `profile_id` int(11) NOT NULL,
                        `product_id` int(11) NOT NULL,
                        `created_at` timestamp NULL DEFAULT current_timestamp(),
                        CONSTRAINT `profile_product_profile_id_fk` FOREIGN KEY (`profile_id`) REFERENCES `profile` (`id`) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
                    ALTER TABLE `profile_product`
                        ADD PRIMARY KEY (`id`),
                        ADD KEY `profile_id_ibfk_2` (`profile_id`),
                        ADD KEY `profile_product_ibfk_1` (`product_id`);
                    ALTER TABLE `profile_product` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
                    COMMIT;
            	");

                $defaultAttribute = [
                    ['code' => 'source_product_id', 'label' => 'Source Product Id'],
                    ['code' => 'store_id', 'label' => 'Store Id'],
                    ['code' => 'title', 'label' => 'Title'],
                    ['code' => 'short_description', 'label' => 'Short Description'],
                    ['code' => 'long_description', 'label' => 'Long Description'],
                    ['code' => 'type', 'label' => 'Type'],
                    ['code' => 'attribute_set', 'label' => 'Attribute Set'],
                    ['code' => 'source_variant_id', 'label' => 'Source Variant Id'],
                    ['code' => 'sku', 'label' => 'SKU'],
                    ['code' => 'price', 'label' => 'Price'],
                    ['code' => 'quantity', 'label' => 'Quantity'],
                    ['code' => 'position', 'label' => 'Position'],
                    ['code' => 'main_image', 'label' => 'Main Image'],
                    ['code' => 'additional_images', 'label' => 'Additional Images'],
                    ['code' => 'weight', 'label' => 'Weight'],
                    ['code' => 'weight_unit', 'label' => 'Weight Unit'],
                    ['code' => 'dimensions', 'label' => 'Dimensions'],
                    ['code' => 'upc', 'label' => 'UPC'],
                    ['code' => 'gtin', 'label' => 'GTIN'],
                    ['code' => 'isbn', 'label' => 'ISBN'],
                    ['code' => 'ean', 'label' => 'EAN'],
                    ['code' => 'asin', 'label' => 'ASIN'],
                    ['code' => 'mpn', 'label' => 'MPN'],
                    ['code' => 'status', 'label' => 'Status']
                ];

                foreach ($defaultAttribute as $data) {
                    $attribute = new \App\Connector\Models\ProductAttribute();
                    $attribute->code = $data['code'];
                    $attribute->label = $data['label'];
                    $attribute->frontend_type = 'textfield';
                    $attribute->is_required = 0;
                    $attribute->is_unique = 0;
                    $attribute->apply_on = 'base';
                    $attribute->merchant_id = 1;
                    $attribute->source_model = '';
                    $attribute->is_config = isset($data['is_config']) ? $data['is_config'] : 0;
                    $attribute->save();
                }
                */
            }
        });
    }
}