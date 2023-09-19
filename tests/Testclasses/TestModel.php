<?php
namespace PhilippR\Atk4\Token\Tests\testclasses;

use secondarymodelforatk\SecondaryModelRelationTrait;
use PhilippR\Atk4\Token\Token;

class TestModel extends \Atk4\Data\Model
{
    use SecondaryModelRelationTrait;

    public $table = 'test_model';

    protected function init(): void
    {
      parent::init();
      $this->addField('name');
      $this->addSecondaryModelHasMany(Token::class);
    }
}