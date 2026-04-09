@extends('emails.layouts.master')

@section('title', 'Notification')

@section('content')
@php
    $formattedHtml = $bodyHtml;

    // Convert data blocks with <br> into beautiful tables
    $formattedHtml = preg_replace_callback('/<p>\s*(<strong>.*?(?:<br\s*\/?>).*?)<\/p>/is', function($matches) {
        $lines = preg_split('/<br\s*\/?>/i', $matches[1]);
        $tableHtml = '<div style="background-color:#EBF5F1;border-radius:12px;margin:24px 0;"><table width="100%" cellpadding="0" cellspacing="0" border="0" style="font-size:15px;color:#4A626A;line-height:1.6;">';
        
        $count = count($lines);
        $i = 0;
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            $isLast = (++$i === $count);
            $paddingStyle = $isLast ? 'padding:14px 24px;' : ($i === 1 ? 'padding:24px 24px 14px 24px;' : 'padding:14px 24px;');
            $borderStyle = $isLast ? '' : 'border-bottom:1px solid #D6E8E2;';
            
            if (preg_match('/<strong>(.*?)<\/strong>(.*)/is', $line, $parts)) {
                $key = strip_tags($parts[1]);
                $val = trim($parts[2]);
                $key = rtrim($key, ':');
                $tableHtml .= '<tr><td style="'.$paddingStyle.$borderStyle.'color:#183A41;font-weight:700;" width="45%">' . $key . ':</td><td style="'.$paddingStyle.$borderStyle.'text-align:right;font-weight:800;color:#419B88;">' . $val . '</td></tr>';
            } else {
                $tableHtml .= '<tr><td colspan="2" style="'.$paddingStyle.$borderStyle.'">' . $line . '</td></tr>';
            }
        }
        $tableHtml .= '</table></div>';
        return $tableHtml;
    }, $formattedHtml);
    
    // Style any remaining solitary <strong> blocks (like "Remark", "Generated at", etc)
    $formattedHtml = preg_replace('/<p>\s*<strong>(Generated at|Welcome at|Last Updated):\s*<\/strong>\s*(.*?)<\/p>/i', '<p style="margin-top:32px;font-size:13px;color:#8B9BA7;">$1: $2</p>', $formattedHtml);
    
    // Style standard paragraphs
    $formattedHtml = str_replace('<p>', '<p style="margin:0 0 16px 0;">', $formattedHtml);
@endphp
    <tr>
        <td style="padding:40px;background-color:#ffffff;" align="left" class="dynamic-content">
            <div style="font-family:'Plus Jakarta Sans',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;font-size:15px;line-height:1.6;color:#546875;text-align:left;">
                {!! $formattedHtml !!}
            </div>
        </td>
    </tr>
@endsection
