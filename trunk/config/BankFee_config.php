<?php
 $Config['BankFee']=array (
  'T1' => 
  array (
    'min' => '0',
    'max' => '0',
    'percent' => '0',
    'remark' => '本行同城免手续费',
  ),
  'T2' => 
  array (
    'min' => '1',
    'max' => '50',
    'percent' => '1',
    'remark' => '转账金额的1%，最低1元/笔，最高50元/笔。 ',
  ),
  'T3' => 
  array (
    'min' => '2',
    'max' => '50',
    'percent' => '2',
    'remark' => '转款金额的2%，最低1元/笔，最高50元/笔。 ',
  ),
  'T4' => 
  array (
    'min' => '3',
    'max' => '50',
    'percent' => '3',
    'remark' => '转账金额的3%，最低1元/笔，最高50元/笔。 ',
  ),
  'basic' => 
  array (
    'bank' => 'ICBC',
    'location' => '440100',
  ),
);
?>