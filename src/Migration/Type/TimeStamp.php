<?php
    namespace ZubZet\Framework\Migration\Type;

    use Doctrine\DBAL\Platforms\AbstractPlatform;
    use Doctrine\DBAL\Platforms\MySQLPlatform;
    use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
    use Doctrine\DBAL\Types\Type;

    class TimeStamp extends Type {

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string {
        if($platform instanceof MySQLPlatform || $platform instanceof PostgreSQLPlatform) return "TIMESTAMP";
        return "DATETIME";
    }

    public function getName(): string {
        return "timestamp";
    }
}
