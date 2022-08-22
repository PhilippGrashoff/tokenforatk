<?php
namespace tokenforatk\tests\testclasses;

use secondarymodelforatk\SecondaryModelRelationTrait;
use tokenforatk\Token;

class OtherTestModel extends \Atk4\Data\Model
{
    use SecondaryModelRelationTrait;

    public $table = 'other_test_model';

    protected function init(): void
    {
      parent::init();
      $this->addField('name');
      $this->addSecondaryModelHasMany(Token::class);
    }
}