<?php

namespace Contest\Model\Base;

use \DateTime;
use \Exception;
use \PDO;
use Contest\Model\Game as ChildGame;
use Contest\Model\GameI18n as ChildGameI18n;
use Contest\Model\GameI18nQuery as ChildGameI18nQuery;
use Contest\Model\GameQuery as ChildGameQuery;
use Contest\Model\GameVersion as ChildGameVersion;
use Contest\Model\GameVersionQuery as ChildGameVersionQuery;
use Contest\Model\Participate as ChildParticipate;
use Contest\Model\ParticipateQuery as ChildParticipateQuery;
use Contest\Model\Question as ChildQuestion;
use Contest\Model\QuestionQuery as ChildQuestionQuery;
use Contest\Model\QuestionVersionQuery as ChildQuestionVersionQuery;
use Contest\Model\Map\GameTableMap;
use Contest\Model\Map\GameVersionTableMap;
use Contest\Model\Map\QuestionVersionTableMap;
use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\ActiveRecord\ActiveRecordInterface;
use Propel\Runtime\Collection\Collection;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\BadMethodCallException;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Map\TableMap;
use Propel\Runtime\Parser\AbstractParser;
use Propel\Runtime\Util\PropelDateTime;

abstract class Game implements ActiveRecordInterface
{
    /**
     * TableMap class name
     */
    const TABLE_MAP = '\\Contest\\Model\\Map\\GameTableMap';


    /**
     * attribute to determine if this object has previously been saved.
     * @var boolean
     */
    protected $new = true;

    /**
     * attribute to determine whether this object has been deleted.
     * @var boolean
     */
    protected $deleted = false;

    /**
     * The columns that have been modified in current object.
     * Tracking modified columns allows us to only update modified columns.
     * @var array
     */
    protected $modifiedColumns = array();

    /**
     * The (virtual) columns that are added at runtime
     * The formatters can add supplementary columns based on a resultset
     * @var array
     */
    protected $virtualColumns = array();

    /**
     * The value for the id field.
     * @var        int
     */
    protected $id;

    /**
     * The value for the visible field.
     * Note: this column has a database default value of: 0
     * @var        int
     */
    protected $visible;

    /**
     * The value for the created_at field.
     * @var        string
     */
    protected $created_at;

    /**
     * The value for the updated_at field.
     * @var        string
     */
    protected $updated_at;

    /**
     * The value for the version field.
     * Note: this column has a database default value of: 0
     * @var        int
     */
    protected $version;

    /**
     * The value for the version_created_at field.
     * @var        string
     */
    protected $version_created_at;

    /**
     * The value for the version_created_by field.
     * @var        string
     */
    protected $version_created_by;

    /**
     * @var        ObjectCollection|ChildQuestion[] Collection to store aggregation of ChildQuestion objects.
     */
    protected $collQuestions;
    protected $collQuestionsPartial;

    /**
     * @var        ObjectCollection|ChildParticipate[] Collection to store aggregation of ChildParticipate objects.
     */
    protected $collParticipates;
    protected $collParticipatesPartial;

    /**
     * @var        ObjectCollection|ChildGameI18n[] Collection to store aggregation of ChildGameI18n objects.
     */
    protected $collGameI18ns;
    protected $collGameI18nsPartial;

    /**
     * @var        ObjectCollection|ChildGameVersion[] Collection to store aggregation of ChildGameVersion objects.
     */
    protected $collGameVersions;
    protected $collGameVersionsPartial;

    /**
     * Flag to prevent endless save loop, if this object is referenced
     * by another object which falls in this transaction.
     *
     * @var boolean
     */
    protected $alreadyInSave = false;

    // i18n behavior

    /**
     * Current locale
     * @var        string
     */
    protected $currentLocale = 'en_US';

    /**
     * Current translation objects
     * @var        array[ChildGameI18n]
     */
    protected $currentTranslations;

    // versionable behavior


    /**
     * @var bool
     */
    protected $enforceVersion = false;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection
     */
    protected $questionsScheduledForDeletion = null;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection
     */
    protected $participatesScheduledForDeletion = null;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection
     */
    protected $gameI18nsScheduledForDeletion = null;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection
     */
    protected $gameVersionsScheduledForDeletion = null;

    /**
     * Applies default values to this object.
     * This method should be called from the object's constructor (or
     * equivalent initialization method).
     * @see __construct()
     */
    public function applyDefaultValues()
    {
        $this->visible = 0;
        $this->version = 0;
    }

    /**
     * Initializes internal state of Contest\Model\Base\Game object.
     * @see applyDefaults()
     */
    public function __construct()
    {
        $this->applyDefaultValues();
    }

    /**
     * Returns whether the object has been modified.
     *
     * @return boolean True if the object has been modified.
     */
    public function isModified()
    {
        return !!$this->modifiedColumns;
    }

    /**
     * Has specified column been modified?
     *
     * @param  string  $col column fully qualified name (TableMap::TYPE_COLNAME), e.g. Book::AUTHOR_ID
     * @return boolean True if $col has been modified.
     */
    public function isColumnModified($col)
    {
        return $this->modifiedColumns && isset($this->modifiedColumns[$col]);
    }

    /**
     * Get the columns that have been modified in this object.
     * @return array A unique list of the modified column names for this object.
     */
    public function getModifiedColumns()
    {
        return $this->modifiedColumns ? array_keys($this->modifiedColumns) : [];
    }

    /**
     * Returns whether the object has ever been saved.  This will
     * be false, if the object was retrieved from storage or was created
     * and then saved.
     *
     * @return boolean true, if the object has never been persisted.
     */
    public function isNew()
    {
        return $this->new;
    }

    /**
     * Setter for the isNew attribute.  This method will be called
     * by Propel-generated children and objects.
     *
     * @param boolean $b the state of the object.
     */
    public function setNew($b)
    {
        $this->new = (Boolean) $b;
    }

    /**
     * Whether this object has been deleted.
     * @return boolean The deleted state of this object.
     */
    public function isDeleted()
    {
        return $this->deleted;
    }

    /**
     * Specify whether this object has been deleted.
     * @param  boolean $b The deleted state of this object.
     * @return void
     */
    public function setDeleted($b)
    {
        $this->deleted = (Boolean) $b;
    }

    /**
     * Sets the modified state for the object to be false.
     * @param  string $col If supplied, only the specified column is reset.
     * @return void
     */
    public function resetModified($col = null)
    {
        if (null !== $col) {
            if (isset($this->modifiedColumns[$col])) {
                unset($this->modifiedColumns[$col]);
            }
        } else {
            $this->modifiedColumns = array();
        }
    }

    /**
     * Compares this with another <code>Game</code> instance.  If
     * <code>obj</code> is an instance of <code>Game</code>, delegates to
     * <code>equals(Game)</code>.  Otherwise, returns <code>false</code>.
     *
     * @param  mixed   $obj The object to compare to.
     * @return boolean Whether equal to the object specified.
     */
    public function equals($obj)
    {
        $thisclazz = get_class($this);
        if (!is_object($obj) || !($obj instanceof $thisclazz)) {
            return false;
        }

        if ($this === $obj) {
            return true;
        }

        if (null === $this->getPrimaryKey()
            || null === $obj->getPrimaryKey())  {
            return false;
        }

        return $this->getPrimaryKey() === $obj->getPrimaryKey();
    }

    /**
     * If the primary key is not null, return the hashcode of the
     * primary key. Otherwise, return the hash code of the object.
     *
     * @return int Hashcode
     */
    public function hashCode()
    {
        if (null !== $this->getPrimaryKey()) {
            return crc32(serialize($this->getPrimaryKey()));
        }

        return crc32(serialize(clone $this));
    }

    /**
     * Get the associative array of the virtual columns in this object
     *
     * @return array
     */
    public function getVirtualColumns()
    {
        return $this->virtualColumns;
    }

    /**
     * Checks the existence of a virtual column in this object
     *
     * @param  string  $name The virtual column name
     * @return boolean
     */
    public function hasVirtualColumn($name)
    {
        return array_key_exists($name, $this->virtualColumns);
    }

    /**
     * Get the value of a virtual column in this object
     *
     * @param  string $name The virtual column name
     * @return mixed
     *
     * @throws PropelException
     */
    public function getVirtualColumn($name)
    {
        if (!$this->hasVirtualColumn($name)) {
            throw new PropelException(sprintf('Cannot get value of inexistent virtual column %s.', $name));
        }

        return $this->virtualColumns[$name];
    }

    /**
     * Set the value of a virtual column in this object
     *
     * @param string $name  The virtual column name
     * @param mixed  $value The value to give to the virtual column
     *
     * @return Game The current object, for fluid interface
     */
    public function setVirtualColumn($name, $value)
    {
        $this->virtualColumns[$name] = $value;

        return $this;
    }

    /**
     * Logs a message using Propel::log().
     *
     * @param  string  $msg
     * @param  int     $priority One of the Propel::LOG_* logging levels
     * @return boolean
     */
    protected function log($msg, $priority = Propel::LOG_INFO)
    {
        return Propel::log(get_class($this) . ': ' . $msg, $priority);
    }

    /**
     * Populate the current object from a string, using a given parser format
     * <code>
     * $book = new Book();
     * $book->importFrom('JSON', '{"Id":9012,"Title":"Don Juan","ISBN":"0140422161","Price":12.99,"PublisherId":1234,"AuthorId":5678}');
     * </code>
     *
     * @param mixed $parser A AbstractParser instance,
     *                       or a format name ('XML', 'YAML', 'JSON', 'CSV')
     * @param string $data The source data to import from
     *
     * @return Game The current object, for fluid interface
     */
    public function importFrom($parser, $data)
    {
        if (!$parser instanceof AbstractParser) {
            $parser = AbstractParser::getParser($parser);
        }

        $this->fromArray($parser->toArray($data), TableMap::TYPE_PHPNAME);

        return $this;
    }

    /**
     * Export the current object properties to a string, using a given parser format
     * <code>
     * $book = BookQuery::create()->findPk(9012);
     * echo $book->exportTo('JSON');
     *  => {"Id":9012,"Title":"Don Juan","ISBN":"0140422161","Price":12.99,"PublisherId":1234,"AuthorId":5678}');
     * </code>
     *
     * @param  mixed   $parser                 A AbstractParser instance, or a format name ('XML', 'YAML', 'JSON', 'CSV')
     * @param  boolean $includeLazyLoadColumns (optional) Whether to include lazy load(ed) columns. Defaults to TRUE.
     * @return string  The exported data
     */
    public function exportTo($parser, $includeLazyLoadColumns = true)
    {
        if (!$parser instanceof AbstractParser) {
            $parser = AbstractParser::getParser($parser);
        }

        return $parser->fromArray($this->toArray(TableMap::TYPE_PHPNAME, $includeLazyLoadColumns, array(), true));
    }

    /**
     * Clean up internal collections prior to serializing
     * Avoids recursive loops that turn into segmentation faults when serializing
     */
    public function __sleep()
    {
        $this->clearAllReferences();

        return array_keys(get_object_vars($this));
    }

    /**
     * Get the [id] column value.
     *
     * @return   int
     */
    public function getId()
    {

        return $this->id;
    }

    /**
     * Get the [visible] column value.
     *
     * @return   int
     */
    public function getVisible()
    {

        return $this->visible;
    }

    /**
     * Get the [optionally formatted] temporal [created_at] column value.
     *
     *
     * @param      string $format The date/time format string (either date()-style or strftime()-style).
     *                            If format is NULL, then the raw \DateTime object will be returned.
     *
     * @return mixed Formatted date/time value as string or \DateTime object (if format is NULL), NULL if column is NULL, and 0 if column value is 0000-00-00 00:00:00
     *
     * @throws PropelException - if unable to parse/validate the date/time value.
     */
    public function getCreatedAt($format = NULL)
    {
        if ($format === null) {
            return $this->created_at;
        } else {
            return $this->created_at instanceof \DateTime ? $this->created_at->format($format) : null;
        }
    }

    /**
     * Get the [optionally formatted] temporal [updated_at] column value.
     *
     *
     * @param      string $format The date/time format string (either date()-style or strftime()-style).
     *                            If format is NULL, then the raw \DateTime object will be returned.
     *
     * @return mixed Formatted date/time value as string or \DateTime object (if format is NULL), NULL if column is NULL, and 0 if column value is 0000-00-00 00:00:00
     *
     * @throws PropelException - if unable to parse/validate the date/time value.
     */
    public function getUpdatedAt($format = NULL)
    {
        if ($format === null) {
            return $this->updated_at;
        } else {
            return $this->updated_at instanceof \DateTime ? $this->updated_at->format($format) : null;
        }
    }

    /**
     * Get the [version] column value.
     *
     * @return   int
     */
    public function getVersion()
    {

        return $this->version;
    }

    /**
     * Get the [optionally formatted] temporal [version_created_at] column value.
     *
     *
     * @param      string $format The date/time format string (either date()-style or strftime()-style).
     *                            If format is NULL, then the raw \DateTime object will be returned.
     *
     * @return mixed Formatted date/time value as string or \DateTime object (if format is NULL), NULL if column is NULL, and 0 if column value is 0000-00-00 00:00:00
     *
     * @throws PropelException - if unable to parse/validate the date/time value.
     */
    public function getVersionCreatedAt($format = NULL)
    {
        if ($format === null) {
            return $this->version_created_at;
        } else {
            return $this->version_created_at instanceof \DateTime ? $this->version_created_at->format($format) : null;
        }
    }

    /**
     * Get the [version_created_by] column value.
     *
     * @return   string
     */
    public function getVersionCreatedBy()
    {

        return $this->version_created_by;
    }

    /**
     * Set the value of [id] column.
     *
     * @param      int $v new value
     * @return   \Contest\Model\Game The current object (for fluent API support)
     */
    public function setId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->id !== $v) {
            $this->id = $v;
            $this->modifiedColumns[GameTableMap::ID] = true;
        }


        return $this;
    } // setId()

    /**
     * Set the value of [visible] column.
     *
     * @param      int $v new value
     * @return   \Contest\Model\Game The current object (for fluent API support)
     */
    public function setVisible($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->visible !== $v) {
            $this->visible = $v;
            $this->modifiedColumns[GameTableMap::VISIBLE] = true;
        }


        return $this;
    } // setVisible()

    /**
     * Sets the value of [created_at] column to a normalized version of the date/time value specified.
     *
     * @param      mixed $v string, integer (timestamp), or \DateTime value.
     *               Empty strings are treated as NULL.
     * @return   \Contest\Model\Game The current object (for fluent API support)
     */
    public function setCreatedAt($v)
    {
        $dt = PropelDateTime::newInstance($v, null, '\DateTime');
        if ($this->created_at !== null || $dt !== null) {
            if ($dt !== $this->created_at) {
                $this->created_at = $dt;
                $this->modifiedColumns[GameTableMap::CREATED_AT] = true;
            }
        } // if either are not null


        return $this;
    } // setCreatedAt()

    /**
     * Sets the value of [updated_at] column to a normalized version of the date/time value specified.
     *
     * @param      mixed $v string, integer (timestamp), or \DateTime value.
     *               Empty strings are treated as NULL.
     * @return   \Contest\Model\Game The current object (for fluent API support)
     */
    public function setUpdatedAt($v)
    {
        $dt = PropelDateTime::newInstance($v, null, '\DateTime');
        if ($this->updated_at !== null || $dt !== null) {
            if ($dt !== $this->updated_at) {
                $this->updated_at = $dt;
                $this->modifiedColumns[GameTableMap::UPDATED_AT] = true;
            }
        } // if either are not null


        return $this;
    } // setUpdatedAt()

    /**
     * Set the value of [version] column.
     *
     * @param      int $v new value
     * @return   \Contest\Model\Game The current object (for fluent API support)
     */
    public function setVersion($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->version !== $v) {
            $this->version = $v;
            $this->modifiedColumns[GameTableMap::VERSION] = true;
        }


        return $this;
    } // setVersion()

    /**
     * Sets the value of [version_created_at] column to a normalized version of the date/time value specified.
     *
     * @param      mixed $v string, integer (timestamp), or \DateTime value.
     *               Empty strings are treated as NULL.
     * @return   \Contest\Model\Game The current object (for fluent API support)
     */
    public function setVersionCreatedAt($v)
    {
        $dt = PropelDateTime::newInstance($v, null, '\DateTime');
        if ($this->version_created_at !== null || $dt !== null) {
            if ($dt !== $this->version_created_at) {
                $this->version_created_at = $dt;
                $this->modifiedColumns[GameTableMap::VERSION_CREATED_AT] = true;
            }
        } // if either are not null


        return $this;
    } // setVersionCreatedAt()

    /**
     * Set the value of [version_created_by] column.
     *
     * @param      string $v new value
     * @return   \Contest\Model\Game The current object (for fluent API support)
     */
    public function setVersionCreatedBy($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->version_created_by !== $v) {
            $this->version_created_by = $v;
            $this->modifiedColumns[GameTableMap::VERSION_CREATED_BY] = true;
        }


        return $this;
    } // setVersionCreatedBy()

    /**
     * Indicates whether the columns in this object are only set to default values.
     *
     * This method can be used in conjunction with isModified() to indicate whether an object is both
     * modified _and_ has some values set which are non-default.
     *
     * @return boolean Whether the columns in this object are only been set with default values.
     */
    public function hasOnlyDefaultValues()
    {
            if ($this->visible !== 0) {
                return false;
            }

            if ($this->version !== 0) {
                return false;
            }

        // otherwise, everything was equal, so return TRUE
        return true;
    } // hasOnlyDefaultValues()

    /**
     * Hydrates (populates) the object variables with values from the database resultset.
     *
     * An offset (0-based "start column") is specified so that objects can be hydrated
     * with a subset of the columns in the resultset rows.  This is needed, for example,
     * for results of JOIN queries where the resultset row includes columns from two or
     * more tables.
     *
     * @param array   $row       The row returned by DataFetcher->fetch().
     * @param int     $startcol  0-based offset column which indicates which restultset column to start with.
     * @param boolean $rehydrate Whether this object is being re-hydrated from the database.
     * @param string  $indexType The index type of $row. Mostly DataFetcher->getIndexType().
                                  One of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME
     *                            TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *
     * @return int             next starting column
     * @throws PropelException - Any caught Exception will be rewrapped as a PropelException.
     */
    public function hydrate($row, $startcol = 0, $rehydrate = false, $indexType = TableMap::TYPE_NUM)
    {
        try {


            $col = $row[TableMap::TYPE_NUM == $indexType ? 0 + $startcol : GameTableMap::translateFieldName('Id', TableMap::TYPE_PHPNAME, $indexType)];
            $this->id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 1 + $startcol : GameTableMap::translateFieldName('Visible', TableMap::TYPE_PHPNAME, $indexType)];
            $this->visible = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 2 + $startcol : GameTableMap::translateFieldName('CreatedAt', TableMap::TYPE_PHPNAME, $indexType)];
            if ($col === '0000-00-00 00:00:00') {
                $col = null;
            }
            $this->created_at = (null !== $col) ? PropelDateTime::newInstance($col, null, '\DateTime') : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 3 + $startcol : GameTableMap::translateFieldName('UpdatedAt', TableMap::TYPE_PHPNAME, $indexType)];
            if ($col === '0000-00-00 00:00:00') {
                $col = null;
            }
            $this->updated_at = (null !== $col) ? PropelDateTime::newInstance($col, null, '\DateTime') : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 4 + $startcol : GameTableMap::translateFieldName('Version', TableMap::TYPE_PHPNAME, $indexType)];
            $this->version = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 5 + $startcol : GameTableMap::translateFieldName('VersionCreatedAt', TableMap::TYPE_PHPNAME, $indexType)];
            if ($col === '0000-00-00 00:00:00') {
                $col = null;
            }
            $this->version_created_at = (null !== $col) ? PropelDateTime::newInstance($col, null, '\DateTime') : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 6 + $startcol : GameTableMap::translateFieldName('VersionCreatedBy', TableMap::TYPE_PHPNAME, $indexType)];
            $this->version_created_by = (null !== $col) ? (string) $col : null;
            $this->resetModified();

            $this->setNew(false);

            if ($rehydrate) {
                $this->ensureConsistency();
            }

            return $startcol + 7; // 7 = GameTableMap::NUM_HYDRATE_COLUMNS.

        } catch (Exception $e) {
            throw new PropelException("Error populating \Contest\Model\Game object", 0, $e);
        }
    }

    /**
     * Checks and repairs the internal consistency of the object.
     *
     * This method is executed after an already-instantiated object is re-hydrated
     * from the database.  It exists to check any foreign keys to make sure that
     * the objects related to the current object are correct based on foreign key.
     *
     * You can override this method in the stub class, but you should always invoke
     * the base method from the overridden method (i.e. parent::ensureConsistency()),
     * in case your model changes.
     *
     * @throws PropelException
     */
    public function ensureConsistency()
    {
    } // ensureConsistency

    /**
     * Reloads this object from datastore based on primary key and (optionally) resets all associated objects.
     *
     * This will only work if the object has been saved and has a valid primary key set.
     *
     * @param      boolean $deep (optional) Whether to also de-associated any related objects.
     * @param      ConnectionInterface $con (optional) The ConnectionInterface connection to use.
     * @return void
     * @throws PropelException - if this object is deleted, unsaved or doesn't have pk match in db
     */
    public function reload($deep = false, ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("Cannot reload a deleted object.");
        }

        if ($this->isNew()) {
            throw new PropelException("Cannot reload an unsaved object.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(GameTableMap::DATABASE_NAME);
        }

        // We don't need to alter the object instance pool; we're just modifying this instance
        // already in the pool.

        $dataFetcher = ChildGameQuery::create(null, $this->buildPkeyCriteria())->setFormatter(ModelCriteria::FORMAT_STATEMENT)->find($con);
        $row = $dataFetcher->fetch();
        $dataFetcher->close();
        if (!$row) {
            throw new PropelException('Cannot find matching row in the database to reload object values.');
        }
        $this->hydrate($row, 0, true, $dataFetcher->getIndexType()); // rehydrate

        if ($deep) {  // also de-associate any related objects?

            $this->collQuestions = null;

            $this->collParticipates = null;

            $this->collGameI18ns = null;

            $this->collGameVersions = null;

        } // if (deep)
    }

    /**
     * Removes this object from datastore and sets delete attribute.
     *
     * @param      ConnectionInterface $con
     * @return void
     * @throws PropelException
     * @see Game::setDeleted()
     * @see Game::isDeleted()
     */
    public function delete(ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("This object has already been deleted.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection(GameTableMap::DATABASE_NAME);
        }

        $con->beginTransaction();
        try {
            $deleteQuery = ChildGameQuery::create()
                ->filterByPrimaryKey($this->getPrimaryKey());
            $ret = $this->preDelete($con);
            if ($ret) {
                $deleteQuery->delete($con);
                $this->postDelete($con);
                $con->commit();
                $this->setDeleted(true);
            } else {
                $con->commit();
            }
        } catch (Exception $e) {
            $con->rollBack();
            throw $e;
        }
    }

    /**
     * Persists this object to the database.
     *
     * If the object is new, it inserts it; otherwise an update is performed.
     * All modified related objects will also be persisted in the doSave()
     * method.  This method wraps all precipitate database operations in a
     * single transaction.
     *
     * @param      ConnectionInterface $con
     * @return int             The number of rows affected by this insert/update and any referring fk objects' save() operations.
     * @throws PropelException
     * @see doSave()
     */
    public function save(ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("You cannot save an object that has been deleted.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection(GameTableMap::DATABASE_NAME);
        }

        $con->beginTransaction();
        $isInsert = $this->isNew();
        try {
            $ret = $this->preSave($con);
            // versionable behavior
            if ($this->isVersioningNecessary()) {
                $this->setVersion($this->isNew() ? 1 : $this->getLastVersionNumber($con) + 1);
                if (!$this->isColumnModified(GameTableMap::VERSION_CREATED_AT)) {
                    $this->setVersionCreatedAt(time());
                }
                $createVersion = true; // for postSave hook
            }
            if ($isInsert) {
                $ret = $ret && $this->preInsert($con);
                // timestampable behavior
                if (!$this->isColumnModified(GameTableMap::CREATED_AT)) {
                    $this->setCreatedAt(time());
                }
                if (!$this->isColumnModified(GameTableMap::UPDATED_AT)) {
                    $this->setUpdatedAt(time());
                }
            } else {
                $ret = $ret && $this->preUpdate($con);
                // timestampable behavior
                if ($this->isModified() && !$this->isColumnModified(GameTableMap::UPDATED_AT)) {
                    $this->setUpdatedAt(time());
                }
            }
            if ($ret) {
                $affectedRows = $this->doSave($con);
                if ($isInsert) {
                    $this->postInsert($con);
                } else {
                    $this->postUpdate($con);
                }
                $this->postSave($con);
                // versionable behavior
                if (isset($createVersion)) {
                    $this->addVersion($con);
                }
                GameTableMap::addInstanceToPool($this);
            } else {
                $affectedRows = 0;
            }
            $con->commit();

            return $affectedRows;
        } catch (Exception $e) {
            $con->rollBack();
            throw $e;
        }
    }

    /**
     * Performs the work of inserting or updating the row in the database.
     *
     * If the object is new, it inserts it; otherwise an update is performed.
     * All related objects are also updated in this method.
     *
     * @param      ConnectionInterface $con
     * @return int             The number of rows affected by this insert/update and any referring fk objects' save() operations.
     * @throws PropelException
     * @see save()
     */
    protected function doSave(ConnectionInterface $con)
    {
        $affectedRows = 0; // initialize var to track total num of affected rows
        if (!$this->alreadyInSave) {
            $this->alreadyInSave = true;

            if ($this->isNew() || $this->isModified()) {
                // persist changes
                if ($this->isNew()) {
                    $this->doInsert($con);
                } else {
                    $this->doUpdate($con);
                }
                $affectedRows += 1;
                $this->resetModified();
            }

            if ($this->questionsScheduledForDeletion !== null) {
                if (!$this->questionsScheduledForDeletion->isEmpty()) {
                    \Contest\Model\QuestionQuery::create()
                        ->filterByPrimaryKeys($this->questionsScheduledForDeletion->getPrimaryKeys(false))
                        ->delete($con);
                    $this->questionsScheduledForDeletion = null;
                }
            }

                if ($this->collQuestions !== null) {
            foreach ($this->collQuestions as $referrerFK) {
                    if (!$referrerFK->isDeleted() && ($referrerFK->isNew() || $referrerFK->isModified())) {
                        $affectedRows += $referrerFK->save($con);
                    }
                }
            }

            if ($this->participatesScheduledForDeletion !== null) {
                if (!$this->participatesScheduledForDeletion->isEmpty()) {
                    \Contest\Model\ParticipateQuery::create()
                        ->filterByPrimaryKeys($this->participatesScheduledForDeletion->getPrimaryKeys(false))
                        ->delete($con);
                    $this->participatesScheduledForDeletion = null;
                }
            }

                if ($this->collParticipates !== null) {
            foreach ($this->collParticipates as $referrerFK) {
                    if (!$referrerFK->isDeleted() && ($referrerFK->isNew() || $referrerFK->isModified())) {
                        $affectedRows += $referrerFK->save($con);
                    }
                }
            }

            if ($this->gameI18nsScheduledForDeletion !== null) {
                if (!$this->gameI18nsScheduledForDeletion->isEmpty()) {
                    \Contest\Model\GameI18nQuery::create()
                        ->filterByPrimaryKeys($this->gameI18nsScheduledForDeletion->getPrimaryKeys(false))
                        ->delete($con);
                    $this->gameI18nsScheduledForDeletion = null;
                }
            }

                if ($this->collGameI18ns !== null) {
            foreach ($this->collGameI18ns as $referrerFK) {
                    if (!$referrerFK->isDeleted() && ($referrerFK->isNew() || $referrerFK->isModified())) {
                        $affectedRows += $referrerFK->save($con);
                    }
                }
            }

            if ($this->gameVersionsScheduledForDeletion !== null) {
                if (!$this->gameVersionsScheduledForDeletion->isEmpty()) {
                    \Contest\Model\GameVersionQuery::create()
                        ->filterByPrimaryKeys($this->gameVersionsScheduledForDeletion->getPrimaryKeys(false))
                        ->delete($con);
                    $this->gameVersionsScheduledForDeletion = null;
                }
            }

                if ($this->collGameVersions !== null) {
            foreach ($this->collGameVersions as $referrerFK) {
                    if (!$referrerFK->isDeleted() && ($referrerFK->isNew() || $referrerFK->isModified())) {
                        $affectedRows += $referrerFK->save($con);
                    }
                }
            }

            $this->alreadyInSave = false;

        }

        return $affectedRows;
    } // doSave()

    /**
     * Insert the row in the database.
     *
     * @param      ConnectionInterface $con
     *
     * @throws PropelException
     * @see doSave()
     */
    protected function doInsert(ConnectionInterface $con)
    {
        $modifiedColumns = array();
        $index = 0;

        $this->modifiedColumns[GameTableMap::ID] = true;
        if (null !== $this->id) {
            throw new PropelException('Cannot insert a value for auto-increment primary key (' . GameTableMap::ID . ')');
        }

         // check the columns in natural order for more readable SQL queries
        if ($this->isColumnModified(GameTableMap::ID)) {
            $modifiedColumns[':p' . $index++]  = 'ID';
        }
        if ($this->isColumnModified(GameTableMap::VISIBLE)) {
            $modifiedColumns[':p' . $index++]  = 'VISIBLE';
        }
        if ($this->isColumnModified(GameTableMap::CREATED_AT)) {
            $modifiedColumns[':p' . $index++]  = 'CREATED_AT';
        }
        if ($this->isColumnModified(GameTableMap::UPDATED_AT)) {
            $modifiedColumns[':p' . $index++]  = 'UPDATED_AT';
        }
        if ($this->isColumnModified(GameTableMap::VERSION)) {
            $modifiedColumns[':p' . $index++]  = 'VERSION';
        }
        if ($this->isColumnModified(GameTableMap::VERSION_CREATED_AT)) {
            $modifiedColumns[':p' . $index++]  = 'VERSION_CREATED_AT';
        }
        if ($this->isColumnModified(GameTableMap::VERSION_CREATED_BY)) {
            $modifiedColumns[':p' . $index++]  = 'VERSION_CREATED_BY';
        }

        $sql = sprintf(
            'INSERT INTO game (%s) VALUES (%s)',
            implode(', ', $modifiedColumns),
            implode(', ', array_keys($modifiedColumns))
        );

        try {
            $stmt = $con->prepare($sql);
            foreach ($modifiedColumns as $identifier => $columnName) {
                switch ($columnName) {
                    case 'ID':
                        $stmt->bindValue($identifier, $this->id, PDO::PARAM_INT);
                        break;
                    case 'VISIBLE':
                        $stmt->bindValue($identifier, $this->visible, PDO::PARAM_INT);
                        break;
                    case 'CREATED_AT':
                        $stmt->bindValue($identifier, $this->created_at ? $this->created_at->format("Y-m-d H:i:s") : null, PDO::PARAM_STR);
                        break;
                    case 'UPDATED_AT':
                        $stmt->bindValue($identifier, $this->updated_at ? $this->updated_at->format("Y-m-d H:i:s") : null, PDO::PARAM_STR);
                        break;
                    case 'VERSION':
                        $stmt->bindValue($identifier, $this->version, PDO::PARAM_INT);
                        break;
                    case 'VERSION_CREATED_AT':
                        $stmt->bindValue($identifier, $this->version_created_at ? $this->version_created_at->format("Y-m-d H:i:s") : null, PDO::PARAM_STR);
                        break;
                    case 'VERSION_CREATED_BY':
                        $stmt->bindValue($identifier, $this->version_created_by, PDO::PARAM_STR);
                        break;
                }
            }
            $stmt->execute();
        } catch (Exception $e) {
            Propel::log($e->getMessage(), Propel::LOG_ERR);
            throw new PropelException(sprintf('Unable to execute INSERT statement [%s]', $sql), 0, $e);
        }

        try {
            $pk = $con->lastInsertId();
        } catch (Exception $e) {
            throw new PropelException('Unable to get autoincrement id.', 0, $e);
        }
        $this->setId($pk);

        $this->setNew(false);
    }

    /**
     * Update the row in the database.
     *
     * @param      ConnectionInterface $con
     *
     * @return Integer Number of updated rows
     * @see doSave()
     */
    protected function doUpdate(ConnectionInterface $con)
    {
        $selectCriteria = $this->buildPkeyCriteria();
        $valuesCriteria = $this->buildCriteria();

        return $selectCriteria->doUpdate($valuesCriteria, $con);
    }

    /**
     * Retrieves a field from the object by name passed in as a string.
     *
     * @param      string $name name
     * @param      string $type The type of fieldname the $name is of:
     *                     one of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME
     *                     TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *                     Defaults to TableMap::TYPE_PHPNAME.
     * @return mixed Value of field.
     */
    public function getByName($name, $type = TableMap::TYPE_PHPNAME)
    {
        $pos = GameTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);
        $field = $this->getByPosition($pos);

        return $field;
    }

    /**
     * Retrieves a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param      int $pos position in xml schema
     * @return mixed Value of field at $pos
     */
    public function getByPosition($pos)
    {
        switch ($pos) {
            case 0:
                return $this->getId();
                break;
            case 1:
                return $this->getVisible();
                break;
            case 2:
                return $this->getCreatedAt();
                break;
            case 3:
                return $this->getUpdatedAt();
                break;
            case 4:
                return $this->getVersion();
                break;
            case 5:
                return $this->getVersionCreatedAt();
                break;
            case 6:
                return $this->getVersionCreatedBy();
                break;
            default:
                return null;
                break;
        } // switch()
    }

    /**
     * Exports the object as an array.
     *
     * You can specify the key type of the array by passing one of the class
     * type constants.
     *
     * @param     string  $keyType (optional) One of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME,
     *                    TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *                    Defaults to TableMap::TYPE_PHPNAME.
     * @param     boolean $includeLazyLoadColumns (optional) Whether to include lazy loaded columns. Defaults to TRUE.
     * @param     array $alreadyDumpedObjects List of objects to skip to avoid recursion
     * @param     boolean $includeForeignObjects (optional) Whether to include hydrated related objects. Default to FALSE.
     *
     * @return array an associative array containing the field names (as keys) and field values
     */
    public function toArray($keyType = TableMap::TYPE_PHPNAME, $includeLazyLoadColumns = true, $alreadyDumpedObjects = array(), $includeForeignObjects = false)
    {
        if (isset($alreadyDumpedObjects['Game'][$this->getPrimaryKey()])) {
            return '*RECURSION*';
        }
        $alreadyDumpedObjects['Game'][$this->getPrimaryKey()] = true;
        $keys = GameTableMap::getFieldNames($keyType);
        $result = array(
            $keys[0] => $this->getId(),
            $keys[1] => $this->getVisible(),
            $keys[2] => $this->getCreatedAt(),
            $keys[3] => $this->getUpdatedAt(),
            $keys[4] => $this->getVersion(),
            $keys[5] => $this->getVersionCreatedAt(),
            $keys[6] => $this->getVersionCreatedBy(),
        );
        $virtualColumns = $this->virtualColumns;
        foreach ($virtualColumns as $key => $virtualColumn) {
            $result[$key] = $virtualColumn;
        }

        if ($includeForeignObjects) {
            if (null !== $this->collQuestions) {
                $result['Questions'] = $this->collQuestions->toArray(null, true, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
            }
            if (null !== $this->collParticipates) {
                $result['Participates'] = $this->collParticipates->toArray(null, true, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
            }
            if (null !== $this->collGameI18ns) {
                $result['GameI18ns'] = $this->collGameI18ns->toArray(null, true, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
            }
            if (null !== $this->collGameVersions) {
                $result['GameVersions'] = $this->collGameVersions->toArray(null, true, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
            }
        }

        return $result;
    }

    /**
     * Sets a field from the object by name passed in as a string.
     *
     * @param      string $name
     * @param      mixed  $value field value
     * @param      string $type The type of fieldname the $name is of:
     *                     one of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME
     *                     TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *                     Defaults to TableMap::TYPE_PHPNAME.
     * @return void
     */
    public function setByName($name, $value, $type = TableMap::TYPE_PHPNAME)
    {
        $pos = GameTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);

        return $this->setByPosition($pos, $value);
    }

    /**
     * Sets a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param      int $pos position in xml schema
     * @param      mixed $value field value
     * @return void
     */
    public function setByPosition($pos, $value)
    {
        switch ($pos) {
            case 0:
                $this->setId($value);
                break;
            case 1:
                $this->setVisible($value);
                break;
            case 2:
                $this->setCreatedAt($value);
                break;
            case 3:
                $this->setUpdatedAt($value);
                break;
            case 4:
                $this->setVersion($value);
                break;
            case 5:
                $this->setVersionCreatedAt($value);
                break;
            case 6:
                $this->setVersionCreatedBy($value);
                break;
        } // switch()
    }

    /**
     * Populates the object using an array.
     *
     * This is particularly useful when populating an object from one of the
     * request arrays (e.g. $_POST).  This method goes through the column
     * names, checking to see whether a matching key exists in populated
     * array. If so the setByName() method is called for that column.
     *
     * You can specify the key type of the array by additionally passing one
     * of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME,
     * TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     * The default key type is the column's TableMap::TYPE_PHPNAME.
     *
     * @param      array  $arr     An array to populate the object from.
     * @param      string $keyType The type of keys the array uses.
     * @return void
     */
    public function fromArray($arr, $keyType = TableMap::TYPE_PHPNAME)
    {
        $keys = GameTableMap::getFieldNames($keyType);

        if (array_key_exists($keys[0], $arr)) $this->setId($arr[$keys[0]]);
        if (array_key_exists($keys[1], $arr)) $this->setVisible($arr[$keys[1]]);
        if (array_key_exists($keys[2], $arr)) $this->setCreatedAt($arr[$keys[2]]);
        if (array_key_exists($keys[3], $arr)) $this->setUpdatedAt($arr[$keys[3]]);
        if (array_key_exists($keys[4], $arr)) $this->setVersion($arr[$keys[4]]);
        if (array_key_exists($keys[5], $arr)) $this->setVersionCreatedAt($arr[$keys[5]]);
        if (array_key_exists($keys[6], $arr)) $this->setVersionCreatedBy($arr[$keys[6]]);
    }

    /**
     * Build a Criteria object containing the values of all modified columns in this object.
     *
     * @return Criteria The Criteria object containing all modified values.
     */
    public function buildCriteria()
    {
        $criteria = new Criteria(GameTableMap::DATABASE_NAME);

        if ($this->isColumnModified(GameTableMap::ID)) $criteria->add(GameTableMap::ID, $this->id);
        if ($this->isColumnModified(GameTableMap::VISIBLE)) $criteria->add(GameTableMap::VISIBLE, $this->visible);
        if ($this->isColumnModified(GameTableMap::CREATED_AT)) $criteria->add(GameTableMap::CREATED_AT, $this->created_at);
        if ($this->isColumnModified(GameTableMap::UPDATED_AT)) $criteria->add(GameTableMap::UPDATED_AT, $this->updated_at);
        if ($this->isColumnModified(GameTableMap::VERSION)) $criteria->add(GameTableMap::VERSION, $this->version);
        if ($this->isColumnModified(GameTableMap::VERSION_CREATED_AT)) $criteria->add(GameTableMap::VERSION_CREATED_AT, $this->version_created_at);
        if ($this->isColumnModified(GameTableMap::VERSION_CREATED_BY)) $criteria->add(GameTableMap::VERSION_CREATED_BY, $this->version_created_by);

        return $criteria;
    }

    /**
     * Builds a Criteria object containing the primary key for this object.
     *
     * Unlike buildCriteria() this method includes the primary key values regardless
     * of whether or not they have been modified.
     *
     * @return Criteria The Criteria object containing value(s) for primary key(s).
     */
    public function buildPkeyCriteria()
    {
        $criteria = new Criteria(GameTableMap::DATABASE_NAME);
        $criteria->add(GameTableMap::ID, $this->id);

        return $criteria;
    }

    /**
     * Returns the primary key for this object (row).
     * @return   int
     */
    public function getPrimaryKey()
    {
        return $this->getId();
    }

    /**
     * Generic method to set the primary key (id column).
     *
     * @param       int $key Primary key.
     * @return void
     */
    public function setPrimaryKey($key)
    {
        $this->setId($key);
    }

    /**
     * Returns true if the primary key for this object is null.
     * @return boolean
     */
    public function isPrimaryKeyNull()
    {

        return null === $this->getId();
    }

    /**
     * Sets contents of passed object to values from current object.
     *
     * If desired, this method can also make copies of all associated (fkey referrers)
     * objects.
     *
     * @param      object $copyObj An object of \Contest\Model\Game (or compatible) type.
     * @param      boolean $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @param      boolean $makeNew Whether to reset autoincrement PKs and make the object new.
     * @throws PropelException
     */
    public function copyInto($copyObj, $deepCopy = false, $makeNew = true)
    {
        $copyObj->setVisible($this->getVisible());
        $copyObj->setCreatedAt($this->getCreatedAt());
        $copyObj->setUpdatedAt($this->getUpdatedAt());
        $copyObj->setVersion($this->getVersion());
        $copyObj->setVersionCreatedAt($this->getVersionCreatedAt());
        $copyObj->setVersionCreatedBy($this->getVersionCreatedBy());

        if ($deepCopy) {
            // important: temporarily setNew(false) because this affects the behavior of
            // the getter/setter methods for fkey referrer objects.
            $copyObj->setNew(false);

            foreach ($this->getQuestions() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addQuestion($relObj->copy($deepCopy));
                }
            }

            foreach ($this->getParticipates() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addParticipate($relObj->copy($deepCopy));
                }
            }

            foreach ($this->getGameI18ns() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addGameI18n($relObj->copy($deepCopy));
                }
            }

            foreach ($this->getGameVersions() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addGameVersion($relObj->copy($deepCopy));
                }
            }

        } // if ($deepCopy)

        if ($makeNew) {
            $copyObj->setNew(true);
            $copyObj->setId(NULL); // this is a auto-increment column, so set to default value
        }
    }

    /**
     * Makes a copy of this object that will be inserted as a new row in table when saved.
     * It creates a new object filling in the simple attributes, but skipping any primary
     * keys that are defined for the table.
     *
     * If desired, this method can also make copies of all associated (fkey referrers)
     * objects.
     *
     * @param      boolean $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @return                 \Contest\Model\Game Clone of current object.
     * @throws PropelException
     */
    public function copy($deepCopy = false)
    {
        // we use get_class(), because this might be a subclass
        $clazz = get_class($this);
        $copyObj = new $clazz();
        $this->copyInto($copyObj, $deepCopy);

        return $copyObj;
    }


    /**
     * Initializes a collection based on the name of a relation.
     * Avoids crafting an 'init[$relationName]s' method name
     * that wouldn't work when StandardEnglishPluralizer is used.
     *
     * @param      string $relationName The name of the relation to initialize
     * @return void
     */
    public function initRelation($relationName)
    {
        if ('Question' == $relationName) {
            return $this->initQuestions();
        }
        if ('Participate' == $relationName) {
            return $this->initParticipates();
        }
        if ('GameI18n' == $relationName) {
            return $this->initGameI18ns();
        }
        if ('GameVersion' == $relationName) {
            return $this->initGameVersions();
        }
    }

    /**
     * Clears out the collQuestions collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addQuestions()
     */
    public function clearQuestions()
    {
        $this->collQuestions = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collQuestions collection loaded partially.
     */
    public function resetPartialQuestions($v = true)
    {
        $this->collQuestionsPartial = $v;
    }

    /**
     * Initializes the collQuestions collection.
     *
     * By default this just sets the collQuestions collection to an empty array (like clearcollQuestions());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initQuestions($overrideExisting = true)
    {
        if (null !== $this->collQuestions && !$overrideExisting) {
            return;
        }
        $this->collQuestions = new ObjectCollection();
        $this->collQuestions->setModel('\Contest\Model\Question');
    }

    /**
     * Gets an array of ChildQuestion objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildGame is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return Collection|ChildQuestion[] List of ChildQuestion objects
     * @throws PropelException
     */
    public function getQuestions($criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collQuestionsPartial && !$this->isNew();
        if (null === $this->collQuestions || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collQuestions) {
                // return empty collection
                $this->initQuestions();
            } else {
                $collQuestions = ChildQuestionQuery::create(null, $criteria)
                    ->filterByGame($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collQuestionsPartial && count($collQuestions)) {
                        $this->initQuestions(false);

                        foreach ($collQuestions as $obj) {
                            if (false == $this->collQuestions->contains($obj)) {
                                $this->collQuestions->append($obj);
                            }
                        }

                        $this->collQuestionsPartial = true;
                    }

                    reset($collQuestions);

                    return $collQuestions;
                }

                if ($partial && $this->collQuestions) {
                    foreach ($this->collQuestions as $obj) {
                        if ($obj->isNew()) {
                            $collQuestions[] = $obj;
                        }
                    }
                }

                $this->collQuestions = $collQuestions;
                $this->collQuestionsPartial = false;
            }
        }

        return $this->collQuestions;
    }

    /**
     * Sets a collection of Question objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $questions A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return   ChildGame The current object (for fluent API support)
     */
    public function setQuestions(Collection $questions, ConnectionInterface $con = null)
    {
        $questionsToDelete = $this->getQuestions(new Criteria(), $con)->diff($questions);


        $this->questionsScheduledForDeletion = $questionsToDelete;

        foreach ($questionsToDelete as $questionRemoved) {
            $questionRemoved->setGame(null);
        }

        $this->collQuestions = null;
        foreach ($questions as $question) {
            $this->addQuestion($question);
        }

        $this->collQuestions = $questions;
        $this->collQuestionsPartial = false;

        return $this;
    }

    /**
     * Returns the number of related Question objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related Question objects.
     * @throws PropelException
     */
    public function countQuestions(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collQuestionsPartial && !$this->isNew();
        if (null === $this->collQuestions || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collQuestions) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getQuestions());
            }

            $query = ChildQuestionQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByGame($this)
                ->count($con);
        }

        return count($this->collQuestions);
    }

    /**
     * Method called to associate a ChildQuestion object to this object
     * through the ChildQuestion foreign key attribute.
     *
     * @param    ChildQuestion $l ChildQuestion
     * @return   \Contest\Model\Game The current object (for fluent API support)
     */
    public function addQuestion(ChildQuestion $l)
    {
        if ($this->collQuestions === null) {
            $this->initQuestions();
            $this->collQuestionsPartial = true;
        }

        if (!in_array($l, $this->collQuestions->getArrayCopy(), true)) { // only add it if the **same** object is not already associated
            $this->doAddQuestion($l);
        }

        return $this;
    }

    /**
     * @param Question $question The question object to add.
     */
    protected function doAddQuestion($question)
    {
        $this->collQuestions[]= $question;
        $question->setGame($this);
    }

    /**
     * @param  Question $question The question object to remove.
     * @return ChildGame The current object (for fluent API support)
     */
    public function removeQuestion($question)
    {
        if ($this->getQuestions()->contains($question)) {
            $this->collQuestions->remove($this->collQuestions->search($question));
            if (null === $this->questionsScheduledForDeletion) {
                $this->questionsScheduledForDeletion = clone $this->collQuestions;
                $this->questionsScheduledForDeletion->clear();
            }
            $this->questionsScheduledForDeletion[]= clone $question;
            $question->setGame(null);
        }

        return $this;
    }

    /**
     * Clears out the collParticipates collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addParticipates()
     */
    public function clearParticipates()
    {
        $this->collParticipates = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collParticipates collection loaded partially.
     */
    public function resetPartialParticipates($v = true)
    {
        $this->collParticipatesPartial = $v;
    }

    /**
     * Initializes the collParticipates collection.
     *
     * By default this just sets the collParticipates collection to an empty array (like clearcollParticipates());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initParticipates($overrideExisting = true)
    {
        if (null !== $this->collParticipates && !$overrideExisting) {
            return;
        }
        $this->collParticipates = new ObjectCollection();
        $this->collParticipates->setModel('\Contest\Model\Participate');
    }

    /**
     * Gets an array of ChildParticipate objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildGame is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return Collection|ChildParticipate[] List of ChildParticipate objects
     * @throws PropelException
     */
    public function getParticipates($criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collParticipatesPartial && !$this->isNew();
        if (null === $this->collParticipates || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collParticipates) {
                // return empty collection
                $this->initParticipates();
            } else {
                $collParticipates = ChildParticipateQuery::create(null, $criteria)
                    ->filterByGame($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collParticipatesPartial && count($collParticipates)) {
                        $this->initParticipates(false);

                        foreach ($collParticipates as $obj) {
                            if (false == $this->collParticipates->contains($obj)) {
                                $this->collParticipates->append($obj);
                            }
                        }

                        $this->collParticipatesPartial = true;
                    }

                    reset($collParticipates);

                    return $collParticipates;
                }

                if ($partial && $this->collParticipates) {
                    foreach ($this->collParticipates as $obj) {
                        if ($obj->isNew()) {
                            $collParticipates[] = $obj;
                        }
                    }
                }

                $this->collParticipates = $collParticipates;
                $this->collParticipatesPartial = false;
            }
        }

        return $this->collParticipates;
    }

    /**
     * Sets a collection of Participate objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $participates A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return   ChildGame The current object (for fluent API support)
     */
    public function setParticipates(Collection $participates, ConnectionInterface $con = null)
    {
        $participatesToDelete = $this->getParticipates(new Criteria(), $con)->diff($participates);


        $this->participatesScheduledForDeletion = $participatesToDelete;

        foreach ($participatesToDelete as $participateRemoved) {
            $participateRemoved->setGame(null);
        }

        $this->collParticipates = null;
        foreach ($participates as $participate) {
            $this->addParticipate($participate);
        }

        $this->collParticipates = $participates;
        $this->collParticipatesPartial = false;

        return $this;
    }

    /**
     * Returns the number of related Participate objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related Participate objects.
     * @throws PropelException
     */
    public function countParticipates(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collParticipatesPartial && !$this->isNew();
        if (null === $this->collParticipates || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collParticipates) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getParticipates());
            }

            $query = ChildParticipateQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByGame($this)
                ->count($con);
        }

        return count($this->collParticipates);
    }

    /**
     * Method called to associate a ChildParticipate object to this object
     * through the ChildParticipate foreign key attribute.
     *
     * @param    ChildParticipate $l ChildParticipate
     * @return   \Contest\Model\Game The current object (for fluent API support)
     */
    public function addParticipate(ChildParticipate $l)
    {
        if ($this->collParticipates === null) {
            $this->initParticipates();
            $this->collParticipatesPartial = true;
        }

        if (!in_array($l, $this->collParticipates->getArrayCopy(), true)) { // only add it if the **same** object is not already associated
            $this->doAddParticipate($l);
        }

        return $this;
    }

    /**
     * @param Participate $participate The participate object to add.
     */
    protected function doAddParticipate($participate)
    {
        $this->collParticipates[]= $participate;
        $participate->setGame($this);
    }

    /**
     * @param  Participate $participate The participate object to remove.
     * @return ChildGame The current object (for fluent API support)
     */
    public function removeParticipate($participate)
    {
        if ($this->getParticipates()->contains($participate)) {
            $this->collParticipates->remove($this->collParticipates->search($participate));
            if (null === $this->participatesScheduledForDeletion) {
                $this->participatesScheduledForDeletion = clone $this->collParticipates;
                $this->participatesScheduledForDeletion->clear();
            }
            $this->participatesScheduledForDeletion[]= clone $participate;
            $participate->setGame(null);
        }

        return $this;
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this Game is new, it will return
     * an empty collection; or if this Game has previously
     * been saved, it will retrieve related Participates from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in Game.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return Collection|ChildParticipate[] List of ChildParticipate objects
     */
    public function getParticipatesJoinCustomer($criteria = null, $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildParticipateQuery::create(null, $criteria);
        $query->joinWith('Customer', $joinBehavior);

        return $this->getParticipates($query, $con);
    }

    /**
     * Clears out the collGameI18ns collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addGameI18ns()
     */
    public function clearGameI18ns()
    {
        $this->collGameI18ns = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collGameI18ns collection loaded partially.
     */
    public function resetPartialGameI18ns($v = true)
    {
        $this->collGameI18nsPartial = $v;
    }

    /**
     * Initializes the collGameI18ns collection.
     *
     * By default this just sets the collGameI18ns collection to an empty array (like clearcollGameI18ns());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initGameI18ns($overrideExisting = true)
    {
        if (null !== $this->collGameI18ns && !$overrideExisting) {
            return;
        }
        $this->collGameI18ns = new ObjectCollection();
        $this->collGameI18ns->setModel('\Contest\Model\GameI18n');
    }

    /**
     * Gets an array of ChildGameI18n objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildGame is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return Collection|ChildGameI18n[] List of ChildGameI18n objects
     * @throws PropelException
     */
    public function getGameI18ns($criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collGameI18nsPartial && !$this->isNew();
        if (null === $this->collGameI18ns || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collGameI18ns) {
                // return empty collection
                $this->initGameI18ns();
            } else {
                $collGameI18ns = ChildGameI18nQuery::create(null, $criteria)
                    ->filterByGame($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collGameI18nsPartial && count($collGameI18ns)) {
                        $this->initGameI18ns(false);

                        foreach ($collGameI18ns as $obj) {
                            if (false == $this->collGameI18ns->contains($obj)) {
                                $this->collGameI18ns->append($obj);
                            }
                        }

                        $this->collGameI18nsPartial = true;
                    }

                    reset($collGameI18ns);

                    return $collGameI18ns;
                }

                if ($partial && $this->collGameI18ns) {
                    foreach ($this->collGameI18ns as $obj) {
                        if ($obj->isNew()) {
                            $collGameI18ns[] = $obj;
                        }
                    }
                }

                $this->collGameI18ns = $collGameI18ns;
                $this->collGameI18nsPartial = false;
            }
        }

        return $this->collGameI18ns;
    }

    /**
     * Sets a collection of GameI18n objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $gameI18ns A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return   ChildGame The current object (for fluent API support)
     */
    public function setGameI18ns(Collection $gameI18ns, ConnectionInterface $con = null)
    {
        $gameI18nsToDelete = $this->getGameI18ns(new Criteria(), $con)->diff($gameI18ns);


        //since at least one column in the foreign key is at the same time a PK
        //we can not just set a PK to NULL in the lines below. We have to store
        //a backup of all values, so we are able to manipulate these items based on the onDelete value later.
        $this->gameI18nsScheduledForDeletion = clone $gameI18nsToDelete;

        foreach ($gameI18nsToDelete as $gameI18nRemoved) {
            $gameI18nRemoved->setGame(null);
        }

        $this->collGameI18ns = null;
        foreach ($gameI18ns as $gameI18n) {
            $this->addGameI18n($gameI18n);
        }

        $this->collGameI18ns = $gameI18ns;
        $this->collGameI18nsPartial = false;

        return $this;
    }

    /**
     * Returns the number of related GameI18n objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related GameI18n objects.
     * @throws PropelException
     */
    public function countGameI18ns(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collGameI18nsPartial && !$this->isNew();
        if (null === $this->collGameI18ns || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collGameI18ns) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getGameI18ns());
            }

            $query = ChildGameI18nQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByGame($this)
                ->count($con);
        }

        return count($this->collGameI18ns);
    }

    /**
     * Method called to associate a ChildGameI18n object to this object
     * through the ChildGameI18n foreign key attribute.
     *
     * @param    ChildGameI18n $l ChildGameI18n
     * @return   \Contest\Model\Game The current object (for fluent API support)
     */
    public function addGameI18n(ChildGameI18n $l)
    {
        if ($l && $locale = $l->getLocale()) {
            $this->setLocale($locale);
            $this->currentTranslations[$locale] = $l;
        }
        if ($this->collGameI18ns === null) {
            $this->initGameI18ns();
            $this->collGameI18nsPartial = true;
        }

        if (!in_array($l, $this->collGameI18ns->getArrayCopy(), true)) { // only add it if the **same** object is not already associated
            $this->doAddGameI18n($l);
        }

        return $this;
    }

    /**
     * @param GameI18n $gameI18n The gameI18n object to add.
     */
    protected function doAddGameI18n($gameI18n)
    {
        $this->collGameI18ns[]= $gameI18n;
        $gameI18n->setGame($this);
    }

    /**
     * @param  GameI18n $gameI18n The gameI18n object to remove.
     * @return ChildGame The current object (for fluent API support)
     */
    public function removeGameI18n($gameI18n)
    {
        if ($this->getGameI18ns()->contains($gameI18n)) {
            $this->collGameI18ns->remove($this->collGameI18ns->search($gameI18n));
            if (null === $this->gameI18nsScheduledForDeletion) {
                $this->gameI18nsScheduledForDeletion = clone $this->collGameI18ns;
                $this->gameI18nsScheduledForDeletion->clear();
            }
            $this->gameI18nsScheduledForDeletion[]= clone $gameI18n;
            $gameI18n->setGame(null);
        }

        return $this;
    }

    /**
     * Clears out the collGameVersions collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addGameVersions()
     */
    public function clearGameVersions()
    {
        $this->collGameVersions = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collGameVersions collection loaded partially.
     */
    public function resetPartialGameVersions($v = true)
    {
        $this->collGameVersionsPartial = $v;
    }

    /**
     * Initializes the collGameVersions collection.
     *
     * By default this just sets the collGameVersions collection to an empty array (like clearcollGameVersions());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initGameVersions($overrideExisting = true)
    {
        if (null !== $this->collGameVersions && !$overrideExisting) {
            return;
        }
        $this->collGameVersions = new ObjectCollection();
        $this->collGameVersions->setModel('\Contest\Model\GameVersion');
    }

    /**
     * Gets an array of ChildGameVersion objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildGame is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return Collection|ChildGameVersion[] List of ChildGameVersion objects
     * @throws PropelException
     */
    public function getGameVersions($criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collGameVersionsPartial && !$this->isNew();
        if (null === $this->collGameVersions || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collGameVersions) {
                // return empty collection
                $this->initGameVersions();
            } else {
                $collGameVersions = ChildGameVersionQuery::create(null, $criteria)
                    ->filterByGame($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collGameVersionsPartial && count($collGameVersions)) {
                        $this->initGameVersions(false);

                        foreach ($collGameVersions as $obj) {
                            if (false == $this->collGameVersions->contains($obj)) {
                                $this->collGameVersions->append($obj);
                            }
                        }

                        $this->collGameVersionsPartial = true;
                    }

                    reset($collGameVersions);

                    return $collGameVersions;
                }

                if ($partial && $this->collGameVersions) {
                    foreach ($this->collGameVersions as $obj) {
                        if ($obj->isNew()) {
                            $collGameVersions[] = $obj;
                        }
                    }
                }

                $this->collGameVersions = $collGameVersions;
                $this->collGameVersionsPartial = false;
            }
        }

        return $this->collGameVersions;
    }

    /**
     * Sets a collection of GameVersion objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $gameVersions A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return   ChildGame The current object (for fluent API support)
     */
    public function setGameVersions(Collection $gameVersions, ConnectionInterface $con = null)
    {
        $gameVersionsToDelete = $this->getGameVersions(new Criteria(), $con)->diff($gameVersions);


        //since at least one column in the foreign key is at the same time a PK
        //we can not just set a PK to NULL in the lines below. We have to store
        //a backup of all values, so we are able to manipulate these items based on the onDelete value later.
        $this->gameVersionsScheduledForDeletion = clone $gameVersionsToDelete;

        foreach ($gameVersionsToDelete as $gameVersionRemoved) {
            $gameVersionRemoved->setGame(null);
        }

        $this->collGameVersions = null;
        foreach ($gameVersions as $gameVersion) {
            $this->addGameVersion($gameVersion);
        }

        $this->collGameVersions = $gameVersions;
        $this->collGameVersionsPartial = false;

        return $this;
    }

    /**
     * Returns the number of related GameVersion objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related GameVersion objects.
     * @throws PropelException
     */
    public function countGameVersions(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collGameVersionsPartial && !$this->isNew();
        if (null === $this->collGameVersions || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collGameVersions) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getGameVersions());
            }

            $query = ChildGameVersionQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByGame($this)
                ->count($con);
        }

        return count($this->collGameVersions);
    }

    /**
     * Method called to associate a ChildGameVersion object to this object
     * through the ChildGameVersion foreign key attribute.
     *
     * @param    ChildGameVersion $l ChildGameVersion
     * @return   \Contest\Model\Game The current object (for fluent API support)
     */
    public function addGameVersion(ChildGameVersion $l)
    {
        if ($this->collGameVersions === null) {
            $this->initGameVersions();
            $this->collGameVersionsPartial = true;
        }

        if (!in_array($l, $this->collGameVersions->getArrayCopy(), true)) { // only add it if the **same** object is not already associated
            $this->doAddGameVersion($l);
        }

        return $this;
    }

    /**
     * @param GameVersion $gameVersion The gameVersion object to add.
     */
    protected function doAddGameVersion($gameVersion)
    {
        $this->collGameVersions[]= $gameVersion;
        $gameVersion->setGame($this);
    }

    /**
     * @param  GameVersion $gameVersion The gameVersion object to remove.
     * @return ChildGame The current object (for fluent API support)
     */
    public function removeGameVersion($gameVersion)
    {
        if ($this->getGameVersions()->contains($gameVersion)) {
            $this->collGameVersions->remove($this->collGameVersions->search($gameVersion));
            if (null === $this->gameVersionsScheduledForDeletion) {
                $this->gameVersionsScheduledForDeletion = clone $this->collGameVersions;
                $this->gameVersionsScheduledForDeletion->clear();
            }
            $this->gameVersionsScheduledForDeletion[]= clone $gameVersion;
            $gameVersion->setGame(null);
        }

        return $this;
    }

    /**
     * Clears the current object and sets all attributes to their default values
     */
    public function clear()
    {
        $this->id = null;
        $this->visible = null;
        $this->created_at = null;
        $this->updated_at = null;
        $this->version = null;
        $this->version_created_at = null;
        $this->version_created_by = null;
        $this->alreadyInSave = false;
        $this->clearAllReferences();
        $this->applyDefaultValues();
        $this->resetModified();
        $this->setNew(true);
        $this->setDeleted(false);
    }

    /**
     * Resets all references to other model objects or collections of model objects.
     *
     * This method is a user-space workaround for PHP's inability to garbage collect
     * objects with circular references (even in PHP 5.3). This is currently necessary
     * when using Propel in certain daemon or large-volume/high-memory operations.
     *
     * @param      boolean $deep Whether to also clear the references on all referrer objects.
     */
    public function clearAllReferences($deep = false)
    {
        if ($deep) {
            if ($this->collQuestions) {
                foreach ($this->collQuestions as $o) {
                    $o->clearAllReferences($deep);
                }
            }
            if ($this->collParticipates) {
                foreach ($this->collParticipates as $o) {
                    $o->clearAllReferences($deep);
                }
            }
            if ($this->collGameI18ns) {
                foreach ($this->collGameI18ns as $o) {
                    $o->clearAllReferences($deep);
                }
            }
            if ($this->collGameVersions) {
                foreach ($this->collGameVersions as $o) {
                    $o->clearAllReferences($deep);
                }
            }
        } // if ($deep)

        // i18n behavior
        $this->currentLocale = 'en_US';
        $this->currentTranslations = null;

        $this->collQuestions = null;
        $this->collParticipates = null;
        $this->collGameI18ns = null;
        $this->collGameVersions = null;
    }

    /**
     * Return the string representation of this object
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->exportTo(GameTableMap::DEFAULT_STRING_FORMAT);
    }

    // timestampable behavior

    /**
     * Mark the current object so that the update date doesn't get updated during next save
     *
     * @return     ChildGame The current object (for fluent API support)
     */
    public function keepUpdateDateUnchanged()
    {
        $this->modifiedColumns[GameTableMap::UPDATED_AT] = true;

        return $this;
    }

    // i18n behavior

    /**
     * Sets the locale for translations
     *
     * @param     string $locale Locale to use for the translation, e.g. 'fr_FR'
     *
     * @return    ChildGame The current object (for fluent API support)
     */
    public function setLocale($locale = 'en_US')
    {
        $this->currentLocale = $locale;

        return $this;
    }

    /**
     * Gets the locale for translations
     *
     * @return    string $locale Locale to use for the translation, e.g. 'fr_FR'
     */
    public function getLocale()
    {
        return $this->currentLocale;
    }

    /**
     * Returns the current translation for a given locale
     *
     * @param     string $locale Locale to use for the translation, e.g. 'fr_FR'
     * @param     ConnectionInterface $con an optional connection object
     *
     * @return ChildGameI18n */
    public function getTranslation($locale = 'en_US', ConnectionInterface $con = null)
    {
        if (!isset($this->currentTranslations[$locale])) {
            if (null !== $this->collGameI18ns) {
                foreach ($this->collGameI18ns as $translation) {
                    if ($translation->getLocale() == $locale) {
                        $this->currentTranslations[$locale] = $translation;

                        return $translation;
                    }
                }
            }
            if ($this->isNew()) {
                $translation = new ChildGameI18n();
                $translation->setLocale($locale);
            } else {
                $translation = ChildGameI18nQuery::create()
                    ->filterByPrimaryKey(array($this->getPrimaryKey(), $locale))
                    ->findOneOrCreate($con);
                $this->currentTranslations[$locale] = $translation;
            }
            $this->addGameI18n($translation);
        }

        return $this->currentTranslations[$locale];
    }

    /**
     * Remove the translation for a given locale
     *
     * @param     string $locale Locale to use for the translation, e.g. 'fr_FR'
     * @param     ConnectionInterface $con an optional connection object
     *
     * @return    ChildGame The current object (for fluent API support)
     */
    public function removeTranslation($locale = 'en_US', ConnectionInterface $con = null)
    {
        if (!$this->isNew()) {
            ChildGameI18nQuery::create()
                ->filterByPrimaryKey(array($this->getPrimaryKey(), $locale))
                ->delete($con);
        }
        if (isset($this->currentTranslations[$locale])) {
            unset($this->currentTranslations[$locale]);
        }
        foreach ($this->collGameI18ns as $key => $translation) {
            if ($translation->getLocale() == $locale) {
                unset($this->collGameI18ns[$key]);
                break;
            }
        }

        return $this;
    }

    /**
     * Returns the current translation
     *
     * @param     ConnectionInterface $con an optional connection object
     *
     * @return ChildGameI18n */
    public function getCurrentTranslation(ConnectionInterface $con = null)
    {
        return $this->getTranslation($this->getLocale(), $con);
    }


        /**
         * Get the [title] column value.
         *
         * @return   string
         */
        public function getTitle()
        {
        return $this->getCurrentTranslation()->getTitle();
    }


        /**
         * Set the value of [title] column.
         *
         * @param      string $v new value
         * @return   \Contest\Model\GameI18n The current object (for fluent API support)
         */
        public function setTitle($v)
        {    $this->getCurrentTranslation()->setTitle($v);

        return $this;
    }


        /**
         * Get the [description] column value.
         *
         * @return   string
         */
        public function getDescription()
        {
        return $this->getCurrentTranslation()->getDescription();
    }


        /**
         * Set the value of [description] column.
         *
         * @param      string $v new value
         * @return   \Contest\Model\GameI18n The current object (for fluent API support)
         */
        public function setDescription($v)
        {    $this->getCurrentTranslation()->setDescription($v);

        return $this;
    }

    // versionable behavior

    /**
     * Enforce a new Version of this object upon next save.
     *
     * @return \Contest\Model\Game
     */
    public function enforceVersioning()
    {
        $this->enforceVersion = true;

        return $this;
    }

    /**
     * Checks whether the current state must be recorded as a version
     *
     * @return  boolean
     */
    public function isVersioningNecessary($con = null)
    {
        if ($this->alreadyInSave) {
            return false;
        }

        if ($this->enforceVersion) {
            return true;
        }

        if (ChildGameQuery::isVersioningEnabled() && ($this->isNew() || $this->isModified()) || $this->isDeleted()) {
            return true;
        }
        // to avoid infinite loops, emulate in save
        $this->alreadyInSave = true;
        foreach ($this->getQuestions(null, $con) as $relatedObject) {
            if ($relatedObject->isVersioningNecessary($con)) {
                $this->alreadyInSave = false;

                return true;
            }
        }
        $this->alreadyInSave = false;


        return false;
    }

    /**
     * Creates a version of the current object and saves it.
     *
     * @param   ConnectionInterface $con the connection to use
     *
     * @return  ChildGameVersion A version object
     */
    public function addVersion($con = null)
    {
        $this->enforceVersion = false;

        $version = new ChildGameVersion();
        $version->setId($this->getId());
        $version->setVisible($this->getVisible());
        $version->setCreatedAt($this->getCreatedAt());
        $version->setUpdatedAt($this->getUpdatedAt());
        $version->setVersion($this->getVersion());
        $version->setVersionCreatedAt($this->getVersionCreatedAt());
        $version->setVersionCreatedBy($this->getVersionCreatedBy());
        $version->setGame($this);
        if ($relateds = $this->getQuestions($con)->toKeyValue('Id', 'Version')) {
            $version->setQuestionIds(array_keys($relateds));
            $version->setQuestionVersions(array_values($relateds));
        }
        $version->save($con);

        return $version;
    }

    /**
     * Sets the properties of the current object to the value they had at a specific version
     *
     * @param   integer $versionNumber The version number to read
     * @param   ConnectionInterface $con The connection to use
     *
     * @return  ChildGame The current object (for fluent API support)
     */
    public function toVersion($versionNumber, $con = null)
    {
        $version = $this->getOneVersion($versionNumber, $con);
        if (!$version) {
            throw new PropelException(sprintf('No ChildGame object found with version %d', $version));
        }
        $this->populateFromVersion($version, $con);

        return $this;
    }

    /**
     * Sets the properties of the current object to the value they had at a specific version
     *
     * @param ChildGameVersion $version The version object to use
     * @param ConnectionInterface   $con the connection to use
     * @param array                 $loadedObjects objects that been loaded in a chain of populateFromVersion calls on referrer or fk objects.
     *
     * @return ChildGame The current object (for fluent API support)
     */
    public function populateFromVersion($version, $con = null, &$loadedObjects = array())
    {
        $loadedObjects['ChildGame'][$version->getId()][$version->getVersion()] = $this;
        $this->setId($version->getId());
        $this->setVisible($version->getVisible());
        $this->setCreatedAt($version->getCreatedAt());
        $this->setUpdatedAt($version->getUpdatedAt());
        $this->setVersion($version->getVersion());
        $this->setVersionCreatedAt($version->getVersionCreatedAt());
        $this->setVersionCreatedBy($version->getVersionCreatedBy());
        if ($fkValues = $version->getQuestionIds()) {
            $this->clearQuestions();
            $fkVersions = $version->getQuestionVersions();
            $query = ChildQuestionVersionQuery::create();
            foreach ($fkValues as $key => $value) {
                $c1 = $query->getNewCriterion(QuestionVersionTableMap::ID, $value);
                $c2 = $query->getNewCriterion(QuestionVersionTableMap::VERSION, $fkVersions[$key]);
                $c1->addAnd($c2);
                $query->addOr($c1);
            }
            foreach ($query->find($con) as $relatedVersion) {
                if (isset($loadedObjects['ChildQuestion']) && isset($loadedObjects['ChildQuestion'][$relatedVersion->getId()]) && isset($loadedObjects['ChildQuestion'][$relatedVersion->getId()][$relatedVersion->getVersion()])) {
                    $related = $loadedObjects['ChildQuestion'][$relatedVersion->getId()][$relatedVersion->getVersion()];
                } else {
                    $related = new ChildQuestion();
                    $related->populateFromVersion($relatedVersion, $con, $loadedObjects);
                    $related->setNew(false);
                }
                $this->addQuestion($related);
                $this->collQuestionsPartial = false;
            }
        }

        return $this;
    }

    /**
     * Gets the latest persisted version number for the current object
     *
     * @param   ConnectionInterface $con the connection to use
     *
     * @return  integer
     */
    public function getLastVersionNumber($con = null)
    {
        $v = ChildGameVersionQuery::create()
            ->filterByGame($this)
            ->orderByVersion('desc')
            ->findOne($con);
        if (!$v) {
            return 0;
        }

        return $v->getVersion();
    }

    /**
     * Checks whether the current object is the latest one
     *
     * @param   ConnectionInterface $con the connection to use
     *
     * @return  Boolean
     */
    public function isLastVersion($con = null)
    {
        return $this->getLastVersionNumber($con) == $this->getVersion();
    }

    /**
     * Retrieves a version object for this entity and a version number
     *
     * @param   integer $versionNumber The version number to read
     * @param   ConnectionInterface $con the connection to use
     *
     * @return  ChildGameVersion A version object
     */
    public function getOneVersion($versionNumber, $con = null)
    {
        return ChildGameVersionQuery::create()
            ->filterByGame($this)
            ->filterByVersion($versionNumber)
            ->findOne($con);
    }

    /**
     * Gets all the versions of this object, in incremental order
     *
     * @param   ConnectionInterface $con the connection to use
     *
     * @return  ObjectCollection A list of ChildGameVersion objects
     */
    public function getAllVersions($con = null)
    {
        $criteria = new Criteria();
        $criteria->addAscendingOrderByColumn(GameVersionTableMap::VERSION);

        return $this->getGameVersions($criteria, $con);
    }

    /**
     * Compares the current object with another of its version.
     * <code>
     * print_r($book->compareVersion(1));
     * => array(
     *   '1' => array('Title' => 'Book title at version 1'),
     *   '2' => array('Title' => 'Book title at version 2')
     * );
     * </code>
     *
     * @param   integer             $versionNumber
     * @param   string              $keys Main key used for the result diff (versions|columns)
     * @param   ConnectionInterface $con the connection to use
     * @param   array               $ignoredColumns  The columns to exclude from the diff.
     *
     * @return  array A list of differences
     */
    public function compareVersion($versionNumber, $keys = 'columns', $con = null, $ignoredColumns = array())
    {
        $fromVersion = $this->toArray();
        $toVersion = $this->getOneVersion($versionNumber, $con)->toArray();

        return $this->computeDiff($fromVersion, $toVersion, $keys, $ignoredColumns);
    }

    /**
     * Compares two versions of the current object.
     * <code>
     * print_r($book->compareVersions(1, 2));
     * => array(
     *   '1' => array('Title' => 'Book title at version 1'),
     *   '2' => array('Title' => 'Book title at version 2')
     * );
     * </code>
     *
     * @param   integer             $fromVersionNumber
     * @param   integer             $toVersionNumber
     * @param   string              $keys Main key used for the result diff (versions|columns)
     * @param   ConnectionInterface $con the connection to use
     * @param   array               $ignoredColumns  The columns to exclude from the diff.
     *
     * @return  array A list of differences
     */
    public function compareVersions($fromVersionNumber, $toVersionNumber, $keys = 'columns', $con = null, $ignoredColumns = array())
    {
        $fromVersion = $this->getOneVersion($fromVersionNumber, $con)->toArray();
        $toVersion = $this->getOneVersion($toVersionNumber, $con)->toArray();

        return $this->computeDiff($fromVersion, $toVersion, $keys, $ignoredColumns);
    }

    /**
     * Computes the diff between two versions.
     * <code>
     * print_r($book->computeDiff(1, 2));
     * => array(
     *   '1' => array('Title' => 'Book title at version 1'),
     *   '2' => array('Title' => 'Book title at version 2')
     * );
     * </code>
     *
     * @param   array     $fromVersion     An array representing the original version.
     * @param   array     $toVersion       An array representing the destination version.
     * @param   string    $keys            Main key used for the result diff (versions|columns).
     * @param   array     $ignoredColumns  The columns to exclude from the diff.
     *
     * @return  array A list of differences
     */
    protected function computeDiff($fromVersion, $toVersion, $keys = 'columns', $ignoredColumns = array())
    {
        $fromVersionNumber = $fromVersion['Version'];
        $toVersionNumber = $toVersion['Version'];
        $ignoredColumns = array_merge(array(
            'Version',
            'VersionCreatedAt',
            'VersionCreatedBy',
        ), $ignoredColumns);
        $diff = array();
        foreach ($fromVersion as $key => $value) {
            if (in_array($key, $ignoredColumns)) {
                continue;
            }
            if ($toVersion[$key] != $value) {
                switch ($keys) {
                    case 'versions':
                        $diff[$fromVersionNumber][$key] = $value;
                        $diff[$toVersionNumber][$key] = $toVersion[$key];
                        break;
                    default:
                        $diff[$key] = array(
                            $fromVersionNumber => $value,
                            $toVersionNumber => $toVersion[$key],
                        );
                        break;
                }
            }
        }

        return $diff;
    }
    /**
     * retrieve the last $number versions.
     *
     * @param Integer $number the number of record to return.
     * @return PropelCollection|array \Contest\Model\GameVersion[] List of \Contest\Model\GameVersion objects
     */
    public function getLastVersions($number = 10, $criteria = null, $con = null)
    {
        $criteria = ChildGameVersionQuery::create(null, $criteria);
        $criteria->addDescendingOrderByColumn(GameVersionTableMap::VERSION);
        $criteria->limit($number);

        return $this->getGameVersions($criteria, $con);
    }
    /**
     * Code to be run before persisting the object
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preSave(ConnectionInterface $con = null)
    {
        return true;
    }

    /**
     * Code to be run after persisting the object
     * @param ConnectionInterface $con
     */
    public function postSave(ConnectionInterface $con = null)
    {

    }

    /**
     * Code to be run before inserting to database
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preInsert(ConnectionInterface $con = null)
    {
        return true;
    }

    /**
     * Code to be run after inserting to database
     * @param ConnectionInterface $con
     */
    public function postInsert(ConnectionInterface $con = null)
    {

    }

    /**
     * Code to be run before updating the object in database
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preUpdate(ConnectionInterface $con = null)
    {
        return true;
    }

    /**
     * Code to be run after updating the object in database
     * @param ConnectionInterface $con
     */
    public function postUpdate(ConnectionInterface $con = null)
    {

    }

    /**
     * Code to be run before deleting the object in database
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preDelete(ConnectionInterface $con = null)
    {
        return true;
    }

    /**
     * Code to be run after deleting the object in database
     * @param ConnectionInterface $con
     */
    public function postDelete(ConnectionInterface $con = null)
    {

    }


    /**
     * Derived method to catches calls to undefined methods.
     *
     * Provides magic import/export method support (fromXML()/toXML(), fromYAML()/toYAML(), etc.).
     * Allows to define default __call() behavior if you overwrite __call()
     *
     * @param string $name
     * @param mixed  $params
     *
     * @return array|string
     */
    public function __call($name, $params)
    {
        if (0 === strpos($name, 'get')) {
            $virtualColumn = substr($name, 3);
            if ($this->hasVirtualColumn($virtualColumn)) {
                return $this->getVirtualColumn($virtualColumn);
            }

            $virtualColumn = lcfirst($virtualColumn);
            if ($this->hasVirtualColumn($virtualColumn)) {
                return $this->getVirtualColumn($virtualColumn);
            }
        }

        if (0 === strpos($name, 'from')) {
            $format = substr($name, 4);

            return $this->importFrom($format, reset($params));
        }

        if (0 === strpos($name, 'to')) {
            $format = substr($name, 2);
            $includeLazyLoadColumns = isset($params[0]) ? $params[0] : true;

            return $this->exportTo($format, $includeLazyLoadColumns);
        }

        throw new BadMethodCallException(sprintf('Call to undefined method: %s.', $name));
    }

}
