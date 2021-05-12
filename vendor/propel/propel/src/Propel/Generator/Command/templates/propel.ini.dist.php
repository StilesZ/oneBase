; When you're part of a team, you could want to define a common configuration file and commit it into your VCS. But, of
; course, there can be some properties you don't want to share, e.g. database passwords. Propel helps you and looks for
; a propel.yml.dist file too, merging its properties with propel.yml ones. So you can define shared configuration
; properties in propel.yml.dist, committing it in your VCS, and keep propel.yml as private. The properties loaded from
; propel.yml overwrite the ones with the same name, loaded from propel.yml.dist.
;
; For a complete references see: http://propelorm.org/documentation/reference/configuration-file.html

; The directory where Propel expects to find your `schema.xml` file.
propel.paths.schemaDir: <?php echo $schemaDir . PHP_EOL; ?>

; The directory where Propel should output generated object model classes.
propel.paths.phpDir: <?php echo $phpDir . PHP_EOL; ?>

; propel.database.connections.default.adapter: <?php echo $rdbms . PHP_EOL ?>
; propel.database.connections.default.dsn: <?php echo $dsn . PHP_EOL ?>
; propel.database.connections.default.user: <?php echo $user . PHP_EOL ?>
; propel.database.connections.default.password:
; propel.database.connections.default.settings.charset: <?php echo $charset . PHP_EOL ?>
