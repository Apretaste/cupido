ALTER TABLE person ADD COLUMN cupido bit DEFAULT 1;
ALTER TABLE person ADD sexual_orientation ENUM('BI','HETERO','HOMO') CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT 'HETERO';

CREATE TABLE `__cupido_ignores` (
  `email1` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email2` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ignore_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`email1`,`email2`)
);

CREATE TABLE `__cupido_likes` (
  `email1` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email2` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `like_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`email1`,`email2`)
);

CREATE TABLE `_province_distance` (
  `province1` enum('PINAR_DEL_RIO','LA_HABANA','ARTEMISA','MAYABEQUE','MATANZAS','VILLA_CLARA','CIENFUEGOS','SANTI_SPIRITUS','CIEGO_DE_AVILA','CAMAGUEY','LAS_TUNAS','HOLGUIN','GRANMA','SANTIAGO_DE_CUBA','GUANTANAMO','ISLA_DE_LA_JUVENTUD') COLLATE utf8_unicode_ci NOT NULL,
  `province2` enum('PINAR_DEL_RIO','LA_HABANA','ARTEMISA','MAYABEQUE','MATANZAS','VILLA_CLARA','CIENFUEGOS','SANTI_SPIRITUS','CIEGO_DE_AVILA','CAMAGUEY','LAS_TUNAS','HOLGUIN','GRANMA','SANTIAGO_DE_CUBA','GUANTANAMO','ISLA_DE_LA_JUVENTUD') COLLATE utf8_unicode_ci NOT NULL,
  `distance` int(11) NOT NULL,
  PRIMARY KEY (`province1`,`province2`)
);

 
INSERT INTO `_province_distance` VALUES ('PINAR_DEL_RIO', 'PINAR_DEL_RIO', '0');
INSERT INTO `_province_distance` VALUES ('PINAR_DEL_RIO', 'LA_HABANA', '2');
INSERT INTO `_province_distance` VALUES ('PINAR_DEL_RIO', 'ARTEMISA', '1');
INSERT INTO `_province_distance` VALUES ('PINAR_DEL_RIO', 'MAYABEQUE', '1');
INSERT INTO `_province_distance` VALUES ('PINAR_DEL_RIO', 'MATANZAS', '3');
INSERT INTO `_province_distance` VALUES ('PINAR_DEL_RIO', 'CIENFUEGOS', '4');
INSERT INTO `_province_distance` VALUES ('PINAR_DEL_RIO', 'SANTI_SPIRITUS', '5');
INSERT INTO `_province_distance` VALUES ('PINAR_DEL_RIO', 'CIEGO_DE_AVILA', '6');
INSERT INTO `_province_distance` VALUES ('PINAR_DEL_RIO', 'CAMAGUEY', '7');
INSERT INTO `_province_distance` VALUES ('PINAR_DEL_RIO', 'LAS_TUNAS', '8');
INSERT INTO `_province_distance` VALUES ('PINAR_DEL_RIO', 'HOLGUIN', '9');
INSERT INTO `_province_distance` VALUES ('PINAR_DEL_RIO', 'GRANMA', '9');
INSERT INTO `_province_distance` VALUES ('PINAR_DEL_RIO', 'SANTIAGO_DE_CUBA', '10');
INSERT INTO `_province_distance` VALUES ('PINAR_DEL_RIO', 'GUANTANAMO', '11');
INSERT INTO `_province_distance` VALUES ('PINAR_DEL_RIO', 'ISLA_DE_LA_JUVENTUD', '3');
INSERT INTO `_province_distance` VALUES ('LA_HABANA', 'LA_HABANA', '0');
INSERT INTO `_province_distance` VALUES ('LA_HABANA', 'MATANZAS', '1');
INSERT INTO `_province_distance` VALUES ('LA_HABANA', 'VILLA_CLARA', '4');
INSERT INTO `_province_distance` VALUES ('LA_HABANA', 'CIENFUEGOS', '2');
INSERT INTO `_province_distance` VALUES ('LA_HABANA', 'SANTI_SPIRITUS', '3');
INSERT INTO `_province_distance` VALUES ('LA_HABANA', 'CIEGO_DE_AVILA', '4');
INSERT INTO `_province_distance` VALUES ('LA_HABANA', 'CAMAGUEY', '5');
INSERT INTO `_province_distance` VALUES ('LA_HABANA', 'LAS_TUNAS', '6');
INSERT INTO `_province_distance` VALUES ('LA_HABANA', 'HOLGUIN', '7');
INSERT INTO `_province_distance` VALUES ('LA_HABANA', 'GRANMA', '8');
INSERT INTO `_province_distance` VALUES ('LA_HABANA', 'SANTIAGO_DE_CUBA', '9');
INSERT INTO `_province_distance` VALUES ('LA_HABANA', 'GUANTANAMO', '10');
INSERT INTO `_province_distance` VALUES ('LA_HABANA', 'ISLA_DE_LA_JUVENTUD', '1');
INSERT INTO `_province_distance` VALUES ('ARTEMISA', 'LA_HABANA', '1');
INSERT INTO `_province_distance` VALUES ('ARTEMISA', 'ARTEMISA', '0');
INSERT INTO `_province_distance` VALUES ('ARTEMISA', 'MAYABEQUE', '1');
INSERT INTO `_province_distance` VALUES ('ARTEMISA', 'MATANZAS', '2');
INSERT INTO `_province_distance` VALUES ('ARTEMISA', 'VILLA_CLARA', '3');
INSERT INTO `_province_distance` VALUES ('ARTEMISA', 'CIENFUEGOS', '3');
INSERT INTO `_province_distance` VALUES ('ARTEMISA', 'SANTI_SPIRITUS', '4');
INSERT INTO `_province_distance` VALUES ('ARTEMISA', 'CIEGO_DE_AVILA', '6');
INSERT INTO `_province_distance` VALUES ('ARTEMISA', 'LAS_TUNAS', '7');
INSERT INTO `_province_distance` VALUES ('ARTEMISA', 'HOLGUIN', '8');
INSERT INTO `_province_distance` VALUES ('ARTEMISA', 'GRANMA', '8');
INSERT INTO `_province_distance` VALUES ('ARTEMISA', 'SANTIAGO_DE_CUBA', '9');
INSERT INTO `_province_distance` VALUES ('ARTEMISA', 'GUANTANAMO', '10');
INSERT INTO `_province_distance` VALUES ('ARTEMISA', 'ISLA_DE_LA_JUVENTUD', '1');
INSERT INTO `_province_distance` VALUES ('MAYABEQUE', 'LA_HABANA', '1');
INSERT INTO `_province_distance` VALUES ('MAYABEQUE', 'MAYABEQUE', '0');
INSERT INTO `_province_distance` VALUES ('MAYABEQUE', 'MATANZAS', '1');
INSERT INTO `_province_distance` VALUES ('MAYABEQUE', 'VILLA_CLARA', '2');
INSERT INTO `_province_distance` VALUES ('MAYABEQUE', 'CIENFUEGOS', '2');
INSERT INTO `_province_distance` VALUES ('MAYABEQUE', 'SANTI_SPIRITUS', '3');
INSERT INTO `_province_distance` VALUES ('MAYABEQUE', 'CIEGO_DE_AVILA', '4');
INSERT INTO `_province_distance` VALUES ('MAYABEQUE', 'CAMAGUEY', '5');
INSERT INTO `_province_distance` VALUES ('MAYABEQUE', 'LAS_TUNAS', '6');
INSERT INTO `_province_distance` VALUES ('MAYABEQUE', 'HOLGUIN', '7');
INSERT INTO `_province_distance` VALUES ('MAYABEQUE', 'GRANMA', '7');
INSERT INTO `_province_distance` VALUES ('MAYABEQUE', 'SANTIAGO_DE_CUBA', '8');
INSERT INTO `_province_distance` VALUES ('MAYABEQUE', 'GUANTANAMO', '9');
INSERT INTO `_province_distance` VALUES ('MAYABEQUE', 'ISLA_DE_LA_JUVENTUD', '1');
INSERT INTO `_province_distance` VALUES ('MATANZAS', 'MATANZAS', '0');
INSERT INTO `_province_distance` VALUES ('MATANZAS', 'VILLA_CLARA', '1');
INSERT INTO `_province_distance` VALUES ('MATANZAS', 'CIENFUEGOS', '1');
INSERT INTO `_province_distance` VALUES ('MATANZAS', 'SANTI_SPIRITUS', '2');
INSERT INTO `_province_distance` VALUES ('MATANZAS', 'CIEGO_DE_AVILA', '3');
INSERT INTO `_province_distance` VALUES ('MATANZAS', 'CAMAGUEY', '4');
INSERT INTO `_province_distance` VALUES ('MATANZAS', 'LAS_TUNAS', '5');
INSERT INTO `_province_distance` VALUES ('MATANZAS', 'HOLGUIN', '6');
INSERT INTO `_province_distance` VALUES ('MATANZAS', 'GRANMA', '6');
INSERT INTO `_province_distance` VALUES ('MATANZAS', 'SANTIAGO_DE_CUBA', '7');
INSERT INTO `_province_distance` VALUES ('MATANZAS', 'GUANTANAMO', '8');
INSERT INTO `_province_distance` VALUES ('MATANZAS', 'ISLA_DE_LA_JUVENTUD', '3');
INSERT INTO `_province_distance` VALUES ('VILLA_CLARA', 'VILLA_CLARA', '0');
INSERT INTO `_province_distance` VALUES ('VILLA_CLARA', 'CIENFUEGOS', '1');
INSERT INTO `_province_distance` VALUES ('VILLA_CLARA', 'SANTI_SPIRITUS', '2');
INSERT INTO `_province_distance` VALUES ('VILLA_CLARA', 'CIEGO_DE_AVILA', '3');
INSERT INTO `_province_distance` VALUES ('VILLA_CLARA', 'CAMAGUEY', '4');
INSERT INTO `_province_distance` VALUES ('VILLA_CLARA', 'LAS_TUNAS', '5');
INSERT INTO `_province_distance` VALUES ('VILLA_CLARA', 'HOLGUIN', '6');
INSERT INTO `_province_distance` VALUES ('VILLA_CLARA', 'GRANMA', '6');
INSERT INTO `_province_distance` VALUES ('VILLA_CLARA', 'SANTIAGO_DE_CUBA', '7');
INSERT INTO `_province_distance` VALUES ('VILLA_CLARA', 'GUANTANAMO', '8');
INSERT INTO `_province_distance` VALUES ('VILLA_CLARA', 'ISLA_DE_LA_JUVENTUD', '4');
INSERT INTO `_province_distance` VALUES ('CIENFUEGOS', 'CIENFUEGOS', '0');
INSERT INTO `_province_distance` VALUES ('CIENFUEGOS', 'SANTI_SPIRITUS', '1');
INSERT INTO `_province_distance` VALUES ('CIENFUEGOS', 'CIEGO_DE_AVILA', '2');
INSERT INTO `_province_distance` VALUES ('CIENFUEGOS', 'CAMAGUEY', '3');
INSERT INTO `_province_distance` VALUES ('CIENFUEGOS', 'LAS_TUNAS', '4');
INSERT INTO `_province_distance` VALUES ('CIENFUEGOS', 'HOLGUIN', '5');
INSERT INTO `_province_distance` VALUES ('CIENFUEGOS', 'GRANMA', '6');
INSERT INTO `_province_distance` VALUES ('CIENFUEGOS', 'SANTIAGO_DE_CUBA', '7');
INSERT INTO `_province_distance` VALUES ('CIENFUEGOS', 'GUANTANAMO', '8');
INSERT INTO `_province_distance` VALUES ('CIENFUEGOS', 'ISLA_DE_LA_JUVENTUD', '4');
INSERT INTO `_province_distance` VALUES ('SANTI_SPIRITUS', 'SANTI_SPIRITUS', '0');
INSERT INTO `_province_distance` VALUES ('SANTI_SPIRITUS', 'CIEGO_DE_AVILA', '1');
INSERT INTO `_province_distance` VALUES ('SANTI_SPIRITUS', 'CAMAGUEY', '2');
INSERT INTO `_province_distance` VALUES ('SANTI_SPIRITUS', 'LAS_TUNAS', '3');
INSERT INTO `_province_distance` VALUES ('SANTI_SPIRITUS', 'GRANMA', '5');
INSERT INTO `_province_distance` VALUES ('SANTI_SPIRITUS', 'SANTIAGO_DE_CUBA', '6');
INSERT INTO `_province_distance` VALUES ('SANTI_SPIRITUS', 'GUANTANAMO', '7');
INSERT INTO `_province_distance` VALUES ('SANTI_SPIRITUS', 'ISLA_DE_LA_JUVENTUD', '5');
INSERT INTO `_province_distance` VALUES ('CIEGO_DE_AVILA', 'CIEGO_DE_AVILA', '0');
INSERT INTO `_province_distance` VALUES ('CIEGO_DE_AVILA', 'CAMAGUEY', '1');
INSERT INTO `_province_distance` VALUES ('CIEGO_DE_AVILA', 'LAS_TUNAS', '2');
INSERT INTO `_province_distance` VALUES ('CIEGO_DE_AVILA', 'HOLGUIN', '3');
INSERT INTO `_province_distance` VALUES ('CIEGO_DE_AVILA', 'GRANMA', '3');
INSERT INTO `_province_distance` VALUES ('CIEGO_DE_AVILA', 'SANTIAGO_DE_CUBA', '4');
INSERT INTO `_province_distance` VALUES ('CIEGO_DE_AVILA', 'GUANTANAMO', '5');
INSERT INTO `_province_distance` VALUES ('CIEGO_DE_AVILA', 'ISLA_DE_LA_JUVENTUD', '6');
INSERT INTO `_province_distance` VALUES ('CAMAGUEY', 'CAMAGUEY', '0');
INSERT INTO `_province_distance` VALUES ('CAMAGUEY', 'LAS_TUNAS', '1');
INSERT INTO `_province_distance` VALUES ('CAMAGUEY', 'HOLGUIN', '2');
INSERT INTO `_province_distance` VALUES ('CAMAGUEY', 'GRANMA', '2');
INSERT INTO `_province_distance` VALUES ('CAMAGUEY', 'SANTIAGO_DE_CUBA', '3');
INSERT INTO `_province_distance` VALUES ('CAMAGUEY', 'GUANTANAMO', '4');
INSERT INTO `_province_distance` VALUES ('CAMAGUEY', 'ISLA_DE_LA_JUVENTUD', '7');
INSERT INTO `_province_distance` VALUES ('LAS_TUNAS', 'LAS_TUNAS', '0');
INSERT INTO `_province_distance` VALUES ('LAS_TUNAS', 'HOLGUIN', '1');
INSERT INTO `_province_distance` VALUES ('LAS_TUNAS', 'GRANMA', '1');
INSERT INTO `_province_distance` VALUES ('LAS_TUNAS', 'SANTIAGO_DE_CUBA', '2');
INSERT INTO `_province_distance` VALUES ('LAS_TUNAS', 'GUANTANAMO', '3');
INSERT INTO `_province_distance` VALUES ('LAS_TUNAS', 'ISLA_DE_LA_JUVENTUD', '8');
INSERT INTO `_province_distance` VALUES ('HOLGUIN', 'HOLGUIN', '0');
INSERT INTO `_province_distance` VALUES ('HOLGUIN', 'GRANMA', '1');
INSERT INTO `_province_distance` VALUES ('HOLGUIN', 'SANTIAGO_DE_CUBA', '1');
INSERT INTO `_province_distance` VALUES ('HOLGUIN', 'GUANTANAMO', '1');
INSERT INTO `_province_distance` VALUES ('HOLGUIN', 'ISLA_DE_LA_JUVENTUD', '9');
INSERT INTO `_province_distance` VALUES ('GRANMA', 'GRANMA', '0');
INSERT INTO `_province_distance` VALUES ('GRANMA', 'SANTIAGO_DE_CUBA', '1');
INSERT INTO `_province_distance` VALUES ('GRANMA', 'ISLA_DE_LA_JUVENTUD', '10');
INSERT INTO `_province_distance` VALUES ('SANTIAGO_DE_CUBA', 'SANTIAGO_DE_CUBA', '0');
INSERT INTO `_province_distance` VALUES ('SANTIAGO_DE_CUBA', 'GUANTANAMO', '1');
INSERT INTO `_province_distance` VALUES ('SANTIAGO_DE_CUBA', 'ISLA_DE_LA_JUVENTUD', '10');
INSERT INTO `_province_distance` VALUES ('GUANTANAMO', 'GUANTANAMO', '0');
INSERT INTO `_province_distance` VALUES ('GUANTANAMO', 'ISLA_DE_LA_JUVENTUD', '11');

DELIMITER ;;
CREATE FUNCTION `SPLIT_STR`(
  x VARCHAR(255),
  delim VARCHAR(12),
  pos INT
) RETURNS varchar(255) CHARSET latin1
RETURN REPLACE(SUBSTRING(SUBSTRING_INDEX(x, delim, pos),
       LENGTH(SUBSTRING_INDEX(x, delim, pos -1)) + 1),
       delim, '');;
DELIMITER ;
