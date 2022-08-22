<?php declare(strict_types=1);

namespace tokenforatk;

use Atk4\Data\Exception;
use Atk4\Data\Model;
use secondarymodelforatk\SecondaryModel;
use traitsforatkdata\CryptIdTrait;
use traitsforatkdata\UserException;


class Token extends SecondaryModel
{

    use CryptIdTrait;

    public $table = 'token';

    //if this is set, on insert the expiry date is automatically set
    public $expiresAfterInMinutes = 0;

    //how many chars are used for the token
    public $tokenLength = 64;


    protected function init(): void
    {
        parent::init();

        $this->cryptIdFieldName = 'value';

        $this->addField(
            'expires',
            [
                'type' => 'datetime'
            ]
        );

        //before insert, create token string
        $this->onHook(
            Model::HOOK_BEFORE_SAVE,
            function (self $model, $isUpdate) {
                if (!$isUpdate) {
                    $model->setCryptId();
                }
                //set expiration on insert
                if (
                    !$isUpdate
                    && !$model->get('expires')
                    && $model->expiresAfterInMinutes > 0
                ) {
                    $model->set(
                        'expires',
                        (new \DateTime())->modify('+' . $model->expiresAfterInMinutes . ' Minutes')
                    );
                }
            }
        );

        //if token is expired do not load but throw exception
        $this->onHook(
            Model::HOOK_AFTER_LOAD,
            function (self $model) {
                if (
                    $model->get('expires') instanceof \DateTimeInterFace
                    && $model->get('expires') < new \DateTime()
                ) {
                    throw new UserException('Das Token ist abgelaufen.');
                }
            }
        );
    }

    /**
     * returns a long random token, $this->tokenLength long
     */
    protected function generateCryptId(): string
    {
        $return = '';
        for ($i = 0; $i < $this->tokenLength; $i++) {
            $return .= $this->getRandomChar();
        }

        return $return;
    }

    /**
     * Tries to load a Token for a given Entity. Checks if token is meant for this Entity before returning
     */
    public static function loadTokenForEntity(Model $entity, string $tokenString): Token
    {
        if (!$entity->loaded()) {
            throw new Exception('Model must be loaded to check if a token is available for the model');
        }
        $token = new static($entity->persistence);
        $token->tryLoadBy('value', $tokenString);
        if (
            !$token->loaded()
            || $token->get('model_class') !== get_class($entity)
            || $token->get('model_id') != $entity->getId()
        ) {
            throw new UserException(
                'Das Token für diesen/diese ' . $entity->getModelCaption() . ' konnte nicht gefunden werden'
            );
        }

        return $token;
    }

    /**
     * Wraps addSecondaryModelRecord() in order to not do anything stupid with value
     */
    public static function createTokenForEntity(Model $entity): Token
    {
        return $entity->addSecondaryModelRecord(Token::class, '');
    }
}