<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Romanian Sentence Analysis</title>
</head>
<body style="background-color: #1e1e1e; color: #ffffff; font-family: Arial, sans-serif; margin: 20px;">
@if (!empty($text))
    <div style="
                white-space: pre-wrap;
                word-wrap: break-word;
                font-size: 18px;
                line-height: 1.1;
                background-color: #333;
                padding: 10px;
                border: 1px solid #555;
                border-radius: 5px;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.5);">
        {!! str_replace("\n", '<br style="margin: 0; padding: 0;">', e($text)) !!}
    </div>
@endif
<br>
<form method="POST" action="{{ route('detail') }}" style="margin-bottom: 20px;">
    @csrf
    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
        <input type="text" id="question" name="question" value="{{ old('question', '') }}" placeholder="Enter your question" style="
                    flex: 1;
                    padding: 10px;
                    font-size: 16px;
                    border: 1px solid #555;
                    border-radius: 5px;
                    background-color: #333;
                    color: #fff;
                    min-width: 200px;">
        <button type="submit" style="
                    padding: 10px 20px;
                    font-size: 16px;
                    color: #ffffff;
                    background-color: #007bff;
                    border: none;
                    border-radius: 5px;
                    cursor: pointer;
                    min-width: 100px;">
            Send
        </button>
    </div>
</form></body>
</html>
