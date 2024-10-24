@extends('crm.layout')
@section('headerStyles')

@endsection

@section('content')
    <input type="hidden" name="_token" value="{{ csrf_token() }}">

    <div class="container mt-4">
        <div class="mb-3">
            <label for="spokenLanguageSelect" class="form-label">Select the language you speak:</label>
            <select id="spokenLanguageSelect" class="form-select">
                <option value="ru-RU">Russian</option>
                <option value="uk-UA">Ukrainian</option>
                <option value="en-US">English</option>
                <option value="de-DE">German</option>
                <option value="sk-SK">Slovak</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="languageSelect" class="form-label">Select the playback language:</label>
            <select id="languageSelect" class="form-select">
                <option value="ru-RU">Russian</option>
                <option value="uk-UA">Ukrainian</option>
                <option value="en-US">English</option>
                <option value="de-DE">German</option>
                <option value="sk-SK">Slovak</option>
            </select>
        </div>
        <div class="d-flex flex-column gap-3">
            <button id="speak_with_chatgpt" class="btn btn-warning btn-lg recordButton" data-action="speak_with_chatgpt">Chat with ChatGpt</button>
            <button id="speak_with_chatgpt_hd" class="btn btn-info btn-lg recordButton" data-action="speak_with_chatgpt_hd">Chat with ChatGpt HD</button>
            <button id="stop_playback" class="btn btn-danger btn-lg" data-action="stop_playback" disabled>Stop Playback</button>
        </div>

        <div class="chats scroll-y me-n5 pe-5 h-300px h-lg-auto mt-4" style="max-height: 800px; overflow-y: auto;">
        </div>
    </div>
@endsection

@section('footerScripts')
    <script>
        $(document).ready(function() {
            let isListening = false;
            let recognition;
            let voices = [];
            let currentButton = null;
            let groupIndex = 0;
            let currentAudio = null; // Переменная для хранения текущего аудио

            // Проверка поддержки Web Speech API
            if ('webkitSpeechRecognition' in window) {
                recognition = new webkitSpeechRecognition();
                recognition.continuous = true;
                recognition.interimResults = false;
                recognition.lang = $('#spokenLanguageSelect').val();
                recognition.maxAlternatives = 5;

                recognition.onstart = function() {
                    console.log('Началась запись');
                };

                recognition.onresult = function(event) {
                    const transcript = event.results[event.resultIndex][0].transcript;
                    console.log('Распознанный текст: ', transcript);

                    // Добавляем текст в чат
                    appendUserMessage(transcript);

                    // Проверяем, активна ли страница
                    if (!document.hidden) {
                        // Сохраняем текст в буфер обмена, если страница активна
                        navigator.clipboard.writeText(transcript).then(function() {
                            console.log('Текст успешно скопирован в буфер обмена.');
                        }).catch(function(err) {
                            console.log('Документ не в фокусе, пропускаем копирование в буфер обмена.');
                        });
                    } else {
                        console.log('Документ не в фокусе, пропускаем копирование в буфер обмена.');
                    }

                    // Отправляем распознанный текст на сервер
                    sendTranscriptionToServer(transcript, currentButton);
                };

                recognition.onerror = function(event) {
                    console.error('Ошибка распознавания: ', event.error);

                    // Перезапуск при ошибке "no-speech", не сбрасываем кнопку
                    if (event.error === 'no-speech') {
                        console.log('Не обнаружена речь, перезапуск распознавания.');
                        restartRecognition();  // Перезапуск распознавания
                    } else {
                        resetButtons();  // Сброс только при других ошибках
                    }
                };

                recognition.onend = function() {
                    // Перезапуск, если завершено, но нужно продолжать слушать
                    if (isListening && !document.hidden) {
                        try {
                            recognition.start();
                        } catch (e) {
                            console.log("Ошибка при перезапуске:", e);
                        }
                    } else {
                        resetButtons();  // Сброс только если не нужно продолжать слушать
                    }
                };
            } else {
                alert("Ваш браузер не поддерживает Web Speech API для автоматической записи.");
            }

            // Клик по первой кнопке
            $('#speak_with_chatgpt').on('click', function() {
                currentButton = 'speak_with_chatgpt';
                handleRecordButtonClick(this, currentButton);
            });

            // Клик по второй кнопке
            $('#speak_with_chatgpt_hd').on('click', function() {
                currentButton = 'speak_with_chatgpt_hd';
                handleRecordButtonClick(this, currentButton);
            });

            // Клик по кнопке "Остановить воспроизведение"
            $('#stop_playback').on('click', function() {
                stopPlayback();
            });

            // Функция для обработки нажатия на кнопку
            function handleRecordButtonClick(button, action) {
                if (isListening) {
                    recognition.stop();
                    isListening = false;
                    resetButton(button);
                } else {
                    if (recognition) {
                        recognition.lang = $('#spokenLanguageSelect').val();
                        try {
                            recognition.start();
                            isListening = true;
                            $(button).data('original-text', $(button).html());
                            $(button).html('🕗 Listening...');
                        } catch (e) {
                            console.log('Ошибка при запуске распознавания:', e);
                        }
                    }
                }
            }

            // Отправка распознанного текста на сервер
            function sendTranscriptionToServer(transcription, action) {
                const inputLanguage = $('#spokenLanguageSelect').val();
                const playbackLanguage = $('#languageSelect').val();

                $.ajax({
                    url: '/send',
                    type: 'POST',
                    data: {
                        transcription: transcription,
                        inputLanguage: inputLanguage,
                        playbackLanguage: playbackLanguage,
                        button: action,
                        _token: $('input[name="_token"]').val()
                    },
                    success: function(response) {
                        console.log("Результат сервера: ", response);
                        handleServerResponse(response, action);
                    },
                    error: function(xhr, status, error) {
                        console.error("Ошибка при отправке данных на сервер: ", error);
                    }
                });
            }

            // Обрабатываем ответ от сервера
            function handleServerResponse(data, button) {
                switch (button) {
                    case 'speak_with_chatgpt':
                        appendBotMessage(data.text);
                        speakText(data.text, button, data.playback_language);
                        break;
                    case 'speak_with_chatgpt_hd':
                        appendBotMessage(data.text);
                        speakTextHD(data.audio_url, button);
                        break;
                    default:
                        navigator.clipboard.writeText(data.text);
                        resetButton(button);
                        break;
                }
            }

            // Добавление сообщений в чат
            function appendUserMessage(message) {
                const userMessageElem = createUserMessageElement(message, groupIndex);
                $('.chats').append(userMessageElem);
                scrollDown();
                groupIndex++;
            }

            function appendBotMessage(message) {
                const botMessageElem = createBotMessageElement(message, groupIndex);
                $('.chats').append(botMessageElem);
                scrollDown();
                groupIndex++;
            }

            // Создание HTML для сообщения пользователя
            function createUserMessageElement(message, group) {
                message = message.replace(/\n/g, '<br>');
                return $(`<div class="d-flex justify-content-end mb-10 message" data-group="${group}">
            <div class="d-flex flex-column align-items-start">
                <div class="p-5 rounded bg-light-primary text-gray-900 fw-semibold mw-lg-400px text-start">${message}</div>
            </div>
        </div>`);
            }

            // Создание HTML для сообщения бота
            function createBotMessageElement(message, group) {
                message = message.replace(/\n/g, '<br>');
                return $(`<div class="d-flex justify-content-start mb-10 message" data-group="${group}">
            <div class="d-flex flex-column align-items-start">
                <div class="p-5 rounded bg-light-info text-gray-900 fw-semibold mw-lg-400px text-start">${message}</div>
            </div>
        </div>`);
            }

            // Прокрутка вниз для новых сообщений
            function scrollDown() {
                $('.chats').scrollTop($('.chats').prop('scrollHeight'));
            }

            // Сброс кнопки в исходное состояние
            function resetButton(button) {
                const originalText = $(button).data('original-text');
                $(button).html(originalText);
            }

            // Сброс всех кнопок
            function resetButtons() {
                $('.recordButton').each(function() {
                    resetButton(this);
                });
                isListening = false;
            }

            // Воспроизведение текста с помощью Web Speech API
            function speakText(text, button, lang = 'ru-RU') {
                if (voices.length === 0) {
                    window.speechSynthesis.onvoiceschanged = function() {
                        voices = window.speechSynthesis.getVoices();
                        speakText(text, button, lang);
                    };
                    return;
                }

                const utterance = new SpeechSynthesisUtterance(text);
                let selectedVoice = voices.find(voice => voice.lang === lang) || voices.find(voice => voice.lang.startsWith('ru')) || voices[0];

                if (selectedVoice) {
                    utterance.voice = selectedVoice;
                } else {
                    console.error('Голос не найден.');
                }

                window.speechSynthesis.speak(utterance);

                // Активируем кнопку остановки воспроизведения
                $('#stop_playback').prop('disabled', false);

                utterance.onend = function() {
                    $('#stop_playback').prop('disabled', true); // Деактивируем кнопку после окончания
                };
            }

            // Воспроизведение HD-звука
            function speakTextHD(audioUrl, button) {
                currentAudio = new Audio(audioUrl);
                currentAudio.play();

                // Активируем кнопку остановки воспроизведения
                $('#stop_playback').prop('disabled', false);

                currentAudio.onended = function() {
                    $('#stop_playback').prop('disabled', true); // Деактивируем кнопку после окончания
                };
            }

            // Остановка воспроизведения
            function stopPlayback() {
                if (window.speechSynthesis.speaking) {
                    window.speechSynthesis.cancel(); // Останавливаем воспроизведение текста
                }

                if (currentAudio) {
                    currentAudio.pause(); // Останавливаем воспроизведение аудио
                    currentAudio = null;
                }

                $('#stop_playback').prop('disabled', true); // Деактивируем кнопку после остановки
            }

            // Отслеживание переключения вкладок
            document.addEventListener('visibilitychange', function() {
                if (!document.hidden && isListening) {
                    try {
                        recognition.start();
                    } catch (e) {
                        console.log("Распознавание уже запущено:", e);
                    }
                }
            });
        });
    </script>
@endsection
