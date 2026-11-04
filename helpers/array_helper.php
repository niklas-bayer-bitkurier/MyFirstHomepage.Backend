<?php

function arr_to_str($arr) {
  $result = '';
  if (!is_array())
    return $result;
  for ($i = 0; $i < count($arr); $i++) {
    $result .= $arr[$i];
  }
  return $arr;
}
