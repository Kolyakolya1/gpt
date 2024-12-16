<?php

namespace App\Http\Controllers\Ai;

use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;

class AiController extends Controller
{
    protected $parser;
    protected $client;

    public function __construct()
    {
        $this->client = new Client([
                                       'base_uri' => 'https://api.openai.com/v1/',
                                       'headers' => [
                                           'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
                                           'Content-Type' => 'application/json'
                                       ]
                                   ]);
    }

    public function sendAudio(Request $request)
    {
        try {
            $transcription = $request->input('transcription');
            $type = $request->input('button');
            $inputLanguage = $request->input('inputLanguage');
            $playbackLanguage = $request->input('playbackLanguage');

            if ($type == 'speak_with_chatgpt') {
                if ($playbackLanguage == 'ru-RU' || $playbackLanguage == 'uk-UA' || $playbackLanguage == 'sk-SK') {
                    $playbackLanguage = 'ru-RU';
                    return $this->generateGptResponse($type, $transcription, $inputLanguage, $playbackLanguage, 'Ответь на заданный вопрос на русском:');
                } elseif ($playbackLanguage == 'en-US') {
                    return $this->generateGptResponse($type, $transcription, $inputLanguage, $playbackLanguage, 'Answer the given question in English:');
                } elseif ($playbackLanguage == 'de-DE') {
                    return $this->generateGptResponse($type, $transcription, $inputLanguage, $playbackLanguage, 'Beantworte die gestellte Frage auf Deutsch:');
                }
            } elseif ($type == 'text_with_chatgpt') {
                if ($playbackLanguage == 'ru-RU') {
                    return $this->generateGptResponse($type, $transcription, $inputLanguage, $playbackLanguage, 'Ответь на заданный вопрос на русском:');
                } elseif ($playbackLanguage == 'uk-UA') {
                    return $this->generateGptResponse($type, $transcription, $inputLanguage, $playbackLanguage, 'Відповісти на задане питання українською:');
                } elseif ($playbackLanguage == 'en-US') {
                    return $this->generateGptResponse($type, $transcription, $inputLanguage, $playbackLanguage, 'Answer the given question in English:');
                } elseif ($playbackLanguage == 'de-DE') {
                    return $this->generateGptResponse($type, $transcription, $inputLanguage, $playbackLanguage, 'Beantworte die gestellte Frage auf Deutsch:');
                } elseif ($playbackLanguage == 'sk-SK') {
                    return $this->generateGptResponse($type, $transcription, $inputLanguage, $playbackLanguage, 'Odpovedzte na položenú otázku po slovensky:');
                } elseif ($playbackLanguage == 'ro-RO') {
                    return $this->generateGptResponse($type, $transcription, $inputLanguage, $playbackLanguage, 'Răspunde la întrebarea pusă în română:');
                }
            }elseif ($type == 'speak_with_chatgpt_hd') {
                if ($playbackLanguage == 'ru-RU') {
                    $response = $this->generateGptResponse($type, $transcription, $inputLanguage, $playbackLanguage, 'Ответь на заданный вопрос на русском:');
                    return $this->generateHdResponse($type, $transcription, $response, $playbackLanguage);
                } elseif ($playbackLanguage == 'uk-UA') {
                    $response = $this->generateGptResponse($type, $transcription, $inputLanguage, $playbackLanguage, 'Відповісти на задане питання українською:');
                    return $this->generateHdResponse($type, $transcription, $response, $playbackLanguage);
                } elseif ($playbackLanguage == 'en-US') {
                    $response = $this->generateGptResponse($type, $transcription, $inputLanguage, $playbackLanguage, 'Answer the given question in English:');
                    return $this->generateHdResponse($type, $transcription, $response, $playbackLanguage);
                } elseif ($playbackLanguage == 'de-DE') {
                    $response = $this->generateGptResponse($type, $transcription, $inputLanguage, $playbackLanguage, 'Beantworte die gestellte Frage auf Deutsch:');
                    return $this->generateHdResponse($type, $transcription, $response, $playbackLanguage);
                } elseif ($playbackLanguage == 'sk-SK') {
                    $response = $this->generateGptResponse($type, $transcription, $inputLanguage, $playbackLanguage, 'Odpovedzte na položenú otázku po slovensky:');
                    return $this->generateHdResponse($type, $transcription, $response, $playbackLanguage);
                } elseif ($playbackLanguage == 'ro-RO') {
                    $response = $this->generateGptResponse($type, $transcription, $inputLanguage, $playbackLanguage, 'Răspunde la întrebarea pusă în română:');
                    return $this->generateHdResponse($type, $transcription, $response, $playbackLanguage);
                }
            }

            return response()->json(['text' => $transcription], 200);
        } catch (GuzzleException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Генерация ответа от ChatGPT
    private function generateGptResponse($type, $transcription, $inputLanguage, $playbackLanguage, $promptPrefix, $max_tokens = 300 )
    {
        $body = [
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'user', 'content' => $promptPrefix . ' ' . $transcription]
            ],
            'max_tokens' => $max_tokens
        ];
        $response = $this->client->post('chat/completions', ['json' => $body]);
        $data = json_decode($response->getBody()->getContents(), true);
        $interpretedData = $data['choices'][0]['message']['content'] ?? '';

        return response()->json([
                                    'question' => $transcription,
                                    'text' => $interpretedData,
                                    'button' => $type,
                                    'playback_language' => $playbackLanguage
                                ], 200);
    }

    // Генерация HD ответа
    private function generateHdResponse($type, $transcription, $response, $playbackLanguage)
    {
        $interpretedData = $response->getData()->text ?? '';

        // Запрос на создание HD-аудио
        $response = $this->client->request('POST', 'audio/speech', [
            'json' => [
                'model' => 'tts-1',
                'voice' => 'alloy',
                'input' => $interpretedData
            ]
        ]);

        $audio_content = $response->getBody()->getContents();
        $audio_file_path = storage_path('app/public/audio_file.mp3');
        if (file_exists($audio_file_path)) {
            unlink($audio_file_path);  // Удаляем, если файл уже существует
        }
        file_put_contents($audio_file_path, $audio_content);

        $audio_url = url('storage/audio_file.mp3') . '?' . uniqid();
        return response()->json([
                                    'audio_url' => $audio_url,
                                    'text' => $interpretedData,
                                    'button' => $type,

                                ], 200);
    }

    public function parseVoice()
    {
        return view('crm.openai.parse-voice');
    }

    public function romanian()
    {
        $transcription = request()->rom;
        $type = 'speak_with_chatgpt';
        $inputLanguage = 'ro-RO';
        $playbackLanguage = 'ro-RO';

        $response = $this->generateGptResponse($type, $transcription, $inputLanguage, $playbackLanguage, 'Дай детальний розбір речення із перекладом на українську мову:', 500);
        return '<pre>' . $response->getData()->text . '</pre>';

    }

}
