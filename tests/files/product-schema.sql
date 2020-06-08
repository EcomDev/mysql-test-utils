CREATE TABLE `product` (
    `product_id` INT NOT NULL AUTO_INCREMENT,
    `sku` VARCHAR(255) NOT NULL DEFAULT '',
    `type` VARCHAR(255) NOT NULL DEFAULT 'simple',
    PRIMARY KEY (`product_id`),
    UNIQUE `sku` (`sku`)
) ENGINE=InnoDB;

CREATE TABLE `product_data` (
    `product_id` INT NOT NULL,
    `attribute` VARCHAR(64) NOT NULL,
    `value` TEXT NULL,
    UNIQUE `product_id_attribute` (`product_id`, `attribute`)
) ENGINE=InnoDB;