<?php

$output = array();
for ($i = 1; $i <=100; $i++)
{
	if ($i % 5 === 0 && $i % 3 == 0)
	{
		$output[] = 'foobar';
	}
	elseif ($i % 3 === 0)
	{
		$output[] = 'foo';
	}
	elseif ($i % 5 === 0)
	{
		$output[] = 'bar';
	}
	else
	{
		$output[] = $i;
	}
}
echo implode(', ', $output)."\n";