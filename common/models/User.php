<?php
namespace common\models;

use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * User model
 *
 * @property integer $id
 * @property string $username
 * @property string $password_hash
 * @property string $password_reset_token
 * @property string $email
 * @property string $auth_key
 * @property integer $status
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $password write-only password
 */
class User extends ActiveRecord implements IdentityInterface
{
    const STATUS_DELETED = 0; //注销
    const STATUS_ACTIVE = 10; //正常
    const STATUS_INACTIVE = 0;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['status', 'default', 'value' => self::STATUS_ACTIVE],
            ['status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_DELETED]],
        ];
    }

    /**
     * 生成 access-token
     */
    public static function generateApiToken()
    {
        $t = Yii::$app->params['user.token_time'];
        return Yii::$app->security->generateRandomString() . '_' . (time()+$t);
    }

    /**
     * 校验access-token是否有效
     */
    public static function apiTokenIsValid($token)
    {
        if (empty($token)) return false;
        $timestamp = (int) substr($token, strrpos($token, '_') + 1);
        return time() < $timestamp;
    }

    /**
     * $s_uid 子系统uid
     * $s_name 子系统名称
     * $type 子系统身份，用于多用户表 0表示单用户表，1管理员表，2客户表
     */
    public static function addUserBySubsystem($s_uid,$s_name,$type=0)
    {
        $token = static::generateApiToken();
        $user = new User();
        $user->username = $s_name . '_' . $type . '_' . $s_uid;
        $user->access_token = $token;
        $user->setPassword($s_name . '_' . $s_uid . '_' . $type);
        $user->generateAuthKey();
        $user->save();
        $model = new SubsystemIdentity();
        $model->uuid = $user->id;
        $model->s_name = $s_name;
        $model->s_uid = $s_uid;
        $model->type = $type;
        $model->save();
        return $user->save() ? $user : false;
    }

    /**
     * 根据子系统获取权限认证token
     * $s_uid 子系统uid
     * $s_name 子系统名称
     * $type 子系统身份，用于多用户表 0表示单用户表，1管理员表，2客户表
     */
    public static function getAuthorizationBySubsystem($s_uid,$s_name,$type=0)
    {
        $s = SubsystemIdentity::find()->where(['type'=>$type, 's_uid'=>$s_uid, 's_name'=>$s_name])->one();
        if ($s) {
            $user = static::findOne($s->uuid);
            if (static::apiTokenIsValid($user->access_token)) return $user;
            $user->access_token = static::generateApiToken();
            return $user->save() ? $user : false;
        }
        return false;
    }

    /**
     * 根据子系统获取全局uuid
     * $s_uid 子系统uid
     * $s_name 子系统名称
     */
    public static function getUuidBySubsystem($s_uid,$s_name,$type=0)
    {
        $s = SubsystemIdentity::find()->where(['type'=>$type, 's_uid'=>$s_uid, 's_name'=>$s_name])->one();
        return $s->uuid;
    }

    /**
     * 根据token获取子系统身份
     * $s_uid 子系统uid
     * $s_name 子系统名称
     */
    public static function getSidByAccessToken($token)
    {
        $user = static::findIdentityByAccessToken($token);
        $si = SubsystemIdentity::find()->where(['uuid'=>$user->id])->one();
        return $si;
    }

    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        if(!static::apiTokenIsValid($token)) {
            return false;
        }

        return static::findOne(['access_token' => $token,'status' => self::STATUS_ACTIVE]);
    }

    /**
     * Finds user by username
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * Finds user by password reset token
     *
     * @param string $token password reset token
     * @return static|null
     */
    public static function findByPasswordResetToken($token)
    {
        if (!static::isPasswordResetTokenValid($token)) {
            return null;
        }

        return static::findOne([
            'password_reset_token' => $token,
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    /**
     * Finds out if password reset token is valid
     *
     * @param string $token password reset token
     * @return bool
     */
    public static function isPasswordResetTokenValid($token)
    {
        if (empty($token)) {
            return false;
        }

        $timestamp = (int) substr($token, strrpos($token, '_') + 1);
        $expire = Yii::$app->params['user.passwordResetTokenExpire'];
        return $timestamp + $expire >= time();
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return bool if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    /**
     * Generates new password reset token
     */
    public function generatePasswordResetToken()
    {
        $this->password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    /**
     * Removes password reset token
     */
    public function removePasswordResetToken()
    {
        $this->password_reset_token = null;
    }
}
