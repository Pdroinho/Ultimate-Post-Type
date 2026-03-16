<?php
if (!defined('ABSPATH'))
    exit;

/**
 * Classe responsável por interagir com o banco de dados
 * para salvar e recuperar as definições dos esquemas e campos.
 */
class UPT_Schema_Store
{

    // O nome da opção no banco de dados do WordPress onde tudo será armazenado
    private const OPTION_KEY = 'upt_schemas';

    /**
     /**
     * Pega todos os esquemas e seus campos do banco de dados (COM CACHE).
     * @return array Retorna um array com todos os esquemas, ou um array vazio.
     */
    public static function get_schemas()
    {
        $cache_key = 'upt_schemas_cache';
        $schemas = get_transient($cache_key);

        if (false === $schemas) {
            $schemas = get_option(self::OPTION_KEY, []);
            set_transient($cache_key, $schemas, 12 * HOUR_IN_SECONDS);
        }

        return $schemas;
    }

    /**
     * Pega os campos de um esquema específico.
     * @param string $schema_slug O ID (slug) do esquema (ex: 'imovel_residencial').
     * @return array Retorna um array com os campos do esquema, ou um array vazio.
     */
    public static function get_fields_for_schema($schema_slug)
    {
        $schemas = self::get_schemas();
        return isset($schemas[$schema_slug]['fields']) ? $schemas[$schema_slug]['fields'] : [];
    }

    /**
     * Salva o array completo de esquemas no banco de dados e limpa o cache.
     * @param array $schemas O array completo de esquemas para salvar.
     * @return bool Retorna true se foi salvo com sucesso, false caso contrário.
     */
    public static function save_schemas($schemas)
    {
        $result = update_option(self::OPTION_KEY, $schemas);
        if ($result) {
            delete_transient('upt_schemas_cache');
        }
        return $result;
    }
}
