<?php
/**
 * CodeUP yihai using Yii Framework
 * @link http://codeup.orangeit.id/yihai
 * @copyright Copyright (c) 2018 OrangeIT.ID
 * @author Upik Saleh <upxsal@gmail.com>
 */

/**
 * Migration table upluad
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class m000000_000110_create_table_upload extends \codeup\base\Migration
{

    /**
     * @inheritdoc
     */
    public function safeUp()
    {

        $this->createTable('{{%sys_uploaded_file}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(),
            'filename' => $this->string(),
            'size' => $this->integer(),
            'type' => $this->string(64),
            'group' => $this->string(20),
            'created_at' => $this->columnCreatedAt(),
            'created_by' => $this->columnCreatedBy(),
            'updated_at' => $this->columnUpdatedAt(),
            'updated_by' => $this->columnUpdatedBy(),
            ], $this->getTableOptions());
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropTable('{{%sys_uploaded_file}}');
    }
}