<?php

trait GenericCrud
{
    private static $table;
    private static $primaryKey = 'id';
    private static $fields = [];

    public static function initialize(string $table, array $fields, string $primaryKey = 'id')
    {
        self::$table = $table;
        self::$fields = $fields;
        self::$primaryKey = $primaryKey;
    }

    public static function create(array $data): bool
    {
        try {
            $db = self::getDbConnection();
            $allowedFields = array_intersect_key($data, array_flip(self::$fields));

            if (empty($allowedFields)) {
                throw new InvalidArgumentException("No valid fields provided");
            }

            $columns = implode(',', array_keys($allowedFields));
            $placeholders = implode(',', array_fill(0, count($allowedFields), '?'));

            $sql = "INSERT INTO `" . self::$table . "` ($columns) VALUES ($placeholders)";
            $stmt = $db->prepare($sql);

            if (!$stmt) {
                throw new RuntimeException("Failed to prepare statement");
            }

            $values = array_values($allowedFields);
            $types = str_repeat('s', count($values));

            if (!$stmt->bind_param($types, ...$values)) {
                throw new RuntimeException("Failed to bind parameters");
            }

            return $stmt->execute();
        } catch (Exception $e) {
            error_log("CRUD Error: " . $e->getMessage());
            throw $e;
        }
    }

    public static function read($id = null): array
    {
        try {
            $db = self::getDbConnection();
            $sql = "SELECT * FROM `" . self::$table . "`";

            if ($id !== null) {
                $sql .= " WHERE `" . self::$primaryKey . "` = ?";
                $stmt = $db->prepare($sql);
                $stmt->bind_param('i', $id);
            } else {
                $stmt = $db->prepare($sql);
            }

            if (!$stmt) {
                throw new RuntimeException("Failed to prepare statement");
            }

            $stmt->execute();
            $result = $stmt->get_result();

            if (!$result) {
                throw new RuntimeException("Failed to get result");
            }

            return $result->fetch_all(MYSQLI_ASSOC) ?: [];
        } catch (Exception $e) {
            error_log("CRUD Error: " . $e->getMessage());
            throw $e;
        }
    }

    public static function update($id, array $data): bool
    {
        try {
            $db = self::getDbConnection();
            $allowedFields = array_intersect_key($data, array_flip(self::$fields));

            if (empty($allowedFields)) {
                throw new InvalidArgumentException("No valid fields provided");
            }

            $setClauses = [];
            $values = [];

            foreach ($allowedFields as $field => $value) {
                $setClauses[] = "`$field` = ?";
                $values[] = $value;
            }

            $values[] = $id;
            $setClause = implode(', ', $setClauses);

            $sql = "UPDATE `" . self::$table . "` SET $setClause WHERE `" . self::$primaryKey . "` = ?";
            $stmt = $db->prepare($sql);

            if (!$stmt) {
                throw new RuntimeException("Failed to prepare statement");
            }

            $types = str_repeat('s', count($values));

            if (!$stmt->bind_param($types, ...$values)) {
                throw new RuntimeException("Failed to bind parameters");
            }

            return $stmt->execute();
        } catch (Exception $e) {
            error_log("CRUD Error: " . $e->getMessage());
            throw $e;
        }
    }

    public static function delete($id): bool
    {
        try {
            $db = self::getDbConnection();
            $sql = "DELETE FROM `" . self::$table . "` WHERE `" . self::$primaryKey . "` = ?";
            $stmt = $db->prepare($sql);

            if (!$stmt) {
                throw new RuntimeException("Failed to prepare statement");
            }

            if (!$stmt->bind_param('i', $id)) {
                throw new RuntimeException("Failed to bind parameters");
            }

            return $stmt->execute();
        } catch (Exception $e) {
            error_log("CRUD Error: " . $e->getMessage());
            throw $e;
        }
    }

    protected static function getDbConnection()
    {
        static $conn;
        if (!$conn) {
            $conn = Database::getConnection();
        }
        return $conn;
    }
}
