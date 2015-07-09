<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\Common\Cache;

use Aws\DynamoDb\DynamoDBClient;

/**
 * DynamoDB cache provider.
 *
 * @author Taiji Inoue <inudog@gmail.com>
 */
class DynamoDBCache extends CacheProvider
{

    /**
     * The ID field will store the cache key.
     */
    const ID_FIELD = 'k';

    /**
     * The data field will store the serialized PHP value.
     */
    const DATA_FIELD = 'd';

    /**
     * The expiration field will store a date value indicating when the
     * cache entry should expire.
     */
    const EXPIRATION_FIELD = 'e';

    /**
     * @var DynamoDBClient
     */
    private $dynamodb;

    /**
     * @var string DynamoDB cache table name
     */
    private $table;

    /**
     * Constructor.
     *
     * @param DynamoDBClient $dynamodb
     * @param string         $table
     */
    public function __construct(DynamoDBClient $dynamodb, $table)
    {
        $this->dynamodb = $dynamodb;
        $this->table    = (string)$table;
    }

    /**
     * Create cache table
     *
     * @param int $readCapacityUnits
     * @param int $writeCapacityUnits
     */
    public function createTable($readCapacityUnits, $writeCapacityUnits)
    {
        $this->dynamodb->createTable(
            array(
                'TableName'             => $this->table,
                'AttributeDefinitions'  => array(
                    array(
                        'AttributeName' => self::ID_FIELD,
                        'AttributeType' => 'S'
                    )
                ),
                'KeySchema'             => array(
                    array(
                        'AttributeName' => self::ID_FIELD,
                        'KeyType'       => 'HASH'
                    ),
                ),
                'ProvisionedThroughput' => array(
                    'ReadCapacityUnits'  => $readCapacityUnits,
                    'WriteCapacityUnits' => $writeCapacityUnits,
                )
            )
        );
        $this->dynamodb->waitUntilTableExists(
            array(
                'TableName' => $this->table,
            )
        );
    }

    /**
     * Delete cache table
     *
     */
    public function deleteTable()
    {
        $this->dynamodb->deleteTable(
            array(
                'TableName' => $this->table,
            )
        );
        $this->dynamodb->waitUntilTableNotExists(
            array(
                'TableName' => $this->table,
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetch($id)
    {
        $result = $this->getItem($id);
        if ($result === null) {
            return false;
        }

        $item = $result['Item'];
        if ($this->isExpired($item)) {
            $this->doDelete($id);
            return false;
        }

        return unserialize($item[self::DATA_FIELD]['S']);
    }

    /**
     * {@inheritdoc}
     */
    protected function doContains($id)
    {
        $result = $this->getItem($id);

        if (!is_object($result) || $result->getPath('Item') === null) {
            return false;
        }
        if ($this->isExpired($result['Item'])) {
            $this->doDelete($id);
            return false;
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function doSave($id, $data, $lifeTime = 0)
    {
        $item = array(
            self::ID_FIELD   => array('S' => $id),
            self::DATA_FIELD => array('S' => serialize($data))
        );

        if ($lifeTime > 0) {
            $item[self::EXPIRATION_FIELD]['N'] = time() + $lifeTime;
        }
        try {
            $this->dynamodb->putItem(
                array(
                    'TableName' => $this->table,
                    'Item'      => $item,
                )
            );
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doDelete($id)
    {
        try {
            $this->dynamodb->deleteItem(
                array(
                    'TableName' => $this->table,
                    'Key'       => array(
                        self::ID_FIELD => array('S' => $id)
                    )
                )
            );
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doFlush()
    {
        try {
            $tableDesc          = $this->dynamodb->describeTable(array('TableName' => $this->table));
            $readCapacityUnits  = $tableDesc->getPath('Table/ProvisionedThroughput/ReadCapacityUnits');
            $writeCapacityUnits = $tableDesc->getPath('Table/ProvisionedThroughput/WriteCapacityUnits');

            // delete table
            $this->deleteTable();

            // create new table
            $this->createTable($readCapacityUnits, $writeCapacityUnits);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doGetStats()
    {
        $result = $this->dynamodb->describeTable(
            array('TableName' => $this->table)
        );

        $uptime = time() - intval($result->getPath('Table/CreationDateTime'));
        return array(
            Cache::STATS_HITS             => null,
            Cache::STATS_MISSES           => null,
            Cache::STATS_UPTIME           => $uptime,
            Cache::STATS_MEMORY_USAGE     => null,
            Cache::STATS_MEMORY_AVAILABLE => null,
        );
    }

    /**
     * Retrieve Record from DynamoDB
     *
     * @param string $id
     *
     * @return null|\Guzzle\Service\Resource\Model
     */
    protected function getItem($id)
    {
        $args = array(
            'TableName'      => $this->table,
            'Key'            => array(
                self::ID_FIELD => array('S' => $id),
            ),
            'ConsistentRead' => false,
        );
        try {
            $result = $this->dynamodb->getItem($args);
            return $result;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check if the document is expired.
     *
     * @param array $item
     *
     * @return boolean
     */
    protected function isExpired($item)
    {
        if (!isset($item[self::EXPIRATION_FIELD]['N'])) {
            return false;
        }
        if (intval($item[self::EXPIRATION_FIELD]['N']) < time()) {
            return true;
        }
        return false;
    }
}
