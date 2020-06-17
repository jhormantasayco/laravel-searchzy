@php
	$word  = $word  ?? ${config('searchzy.keyword')};
	$class = $class ?? config('searchzy.highlight_class');
@endphp

{!! str_highlight($text, $word, $class) !!}
