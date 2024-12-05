@extends('crm.layout')
@section('headerStyles')
    <style>
        #errorMessages {
            display: block !important;
            visibility: visible !important;
            color: red;
            font-size: 1.5rem;
        }
        .container {
            max-width: 100vw;
            overflow-x: hidden;
            padding-left: 20px; /* Добавляем отступ слева */
        }
    </style>
@endsection

@section('content')
    <input type="hidden" name="_token" value="{{ csrf_token() }}">

    <div class="container mt-4 d-flex flex-column" style="height: 100vh;">
        <div class="d-flex flex-row flex-wrap gap-3 align-items-center">
            <div class="flex-fill">
                <div class="input-group">
                    <span class="input-group-text" style="font-size: 2rem;" data-bs-toggle="tooltip" title="Select the language you will speak">🙊</span>
                    <select id="spokenLanguageSelect" class="form-select" data-bs-toggle="tooltip" title="Choose your spoken language">
                        <option value="ru-RU">Русский</option>
                        <option value="uk-UA">Українська</option>
                        <option value="en-US">English</option>
                        <option value="de-DE">Deutsch</option>
                        <option value="sk-SK">Slovenčina</option>
                        <option value="ro-RO">Romanian</option>
                    </select>
                </div>
            </div>

            <div class="flex-fill">
                <div class="input-group">
                    <span class="input-group-text" style="font-size: 2rem;" data-bs-toggle="tooltip" title="Select the language for response">🙉</span>
                    <select id="languageSelect" class="form-select" data-bs-toggle="tooltip" title="Choose the language for playback">
                        <option value="ru-RU">Русский</option>
                        <option value="uk-UA">Українська</option>
                        <option value="en-US">English</option>
                        <option value="de-DE">Deutsch</option>
                        <option value="sk-SK">Slovenčina</option>
                        <option value="ro-RO">Romanian</option>
                    </select>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button id="text_with_chatgpt" class="btn btn-success btn-lg recordButton" data-action="text_with_chatgpt"
                        data-bs-toggle="tooltip" title="Send text to ChatGPT">
                    <span class="d-flex justify-content-center align-items-center w-100 h-100" style="font-size: 2rem;">✍</span>
                </button>
                <button id="speak_with_chatgpt" class="btn btn-warning btn-lg recordButton" data-action="speak_with_chatgpt"
                        data-bs-toggle="tooltip" title="Speak with ChatGPT">
                    <span class="d-flex justify-content-center align-items-center w-100 h-100" style="font-size: 2rem;">😃</span>
                </button>
                <button id="speak_with_chatgpt_hd" class="btn btn-info btn-lg recordButton" data-action="speak_with_chatgpt_hd"
                        data-bs-toggle="tooltip" title="Speak with HD Audio">
                    <span class="d-flex justify-content-center align-items-center w-100 h-100" style="font-size: 2rem;">😁</span>
                </button>
                <button id="stop_playback" class="btn btn-danger btn-lg" data-action="stop_playback" disabled
                        data-bs-toggle="tooltip" title="Stop playback">
                    <span class="d-flex justify-content-center align-items-center w-100 h-100" style="font-size: 2rem;">🛑</span>
                </button>
            </div>
        </div>

        <div class="chats flex-grow-1 scroll-y me-n5 pe-5 mt-4" style="max-height: calc(100vh - 200px); overflow-y: auto;">
        </div>

        <!-- Блок для вывода ошибок -->
        <div id="errorMessages" class="mb-3 ms-auto" style="color: red;">
            <div id="errorLine"></div>
        </div>
    </div>
@endsection

@section('footerScripts')
    <script>
        $(document).ready(function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl, {
                    trigger: 'hover'  // Этот параметр позволяет тултипу появляться при наведении и исчезать при отведении
                });
            });

            const SPOKEN_LANGUAGE_KEY = 'spokenLanguage';
            const PLAYBACK_LANGUAGE_KEY = 'playbackLanguage';

            // Блок для отображения ошибок
            const errorLine = $('#errorLine');

            // Функция для отображения ошибок
            function showError(message) {
                console.log('Отображение ошибки:', message);  // Лог ошибки в консоль
                $('#errorMessages').show();  // Показываем блок
                $('#errorLine').text(message);  // Устанавливаем текст ошибки
                console.log('Ошибка должна быть показана');  // Дополнительное сообщение для отладки
            }


            // Функция для скрытия ошибок
            function hideError() {
                errorLine.text('');
                $('#errorMessages').hide();
            }

            // Загрузка значений из localStorage при загрузке страницы
            const savedSpokenLanguage = localStorage.getItem(SPOKEN_LANGUAGE_KEY);
            const savedPlaybackLanguage = localStorage.getItem(PLAYBACK_LANGUAGE_KEY);

            if (savedSpokenLanguage) {
                $('#spokenLanguageSelect').val(savedSpokenLanguage);
            }

            if (savedPlaybackLanguage) {
                $('#languageSelect').val(savedPlaybackLanguage);
            }

            // Сохранение значений в localStorage при изменении селектов
            $('#spokenLanguageSelect').on('change', function () {
                const selectedSpokenLanguage = $(this).val();
                localStorage.setItem(SPOKEN_LANGUAGE_KEY, selectedSpokenLanguage);
            });

            $('#languageSelect').on('change', function () {
                const selectedPlaybackLanguage = $(this).val();
                localStorage.setItem(PLAYBACK_LANGUAGE_KEY, selectedPlaybackLanguage);
            });

            let isListening = false;
            let recognition;
            let voices = [];
            let currentButton = null;
            let groupIndex = 0;
            let currentAudio = null;
            let isSpeaking = false;
            let textVoice = null;

            // Проверка поддержки Web Speech API
            if ('webkitSpeechRecognition' in window) {
                recognition = new webkitSpeechRecognition();
                recognition.continuous = true;
                recognition.interimResults = false;
                recognition.lang = $('#spokenLanguageSelect').val();
                recognition.maxAlternatives = 5;

                recognition.onstart = function () {
                    hideError(); // Скрываем ошибки при старте записи
                    console.log('Началась запись');
                };

                recognition.onresult = function (event) {
                    if (isSpeaking === false) {
                        const transcript = event.results[event.resultIndex][0].transcript.trim();
                        console.log('Распознанный текст: ', transcript);

                        if (transcript.length < 3) {
                            showError('Распознанный текст слишком короткий, запрос не будет отправлен.');
                            return; // Не отправляем запрос
                        }

                        // Добавляем текст в чат
                        appendUserMessage(transcript);

                        // Проверяем, активна ли страница
                        if (!document.hidden) {
                            // Сохраняем текст в буфер обмена, если страница активна
                            navigator.clipboard.writeText(transcript).then(function () {
                                console.log('Текст успешно скопирован в буфер обмена.');
                            }).catch(function (err) {
                                showError('Ошибка копирования в буфер обмена.');
                            });
                        }

                        // Отправляем распознанный текст на сервер
                        sendTranscriptionToServer(transcript, currentButton);
                    }
                };

                recognition.onerror = function (event) {
                    console.error('Ошибка распознавания: ', event.error);
                    setTimeout(() => {
                        showError('Ошибка распознавания: ' + event.error);
                    }, 100);  // Небольшая задержка для исключения возможных конфликтов
                };



                recognition.onend = function () {
                    if (isListening && !isSpeaking) {
                        console.log('Попытка перезапуска распознавания');
                        try {
                            recognition.start();
                        } catch (e) {
                            showError("Ошибка при перезапуске распознавания.");
                            console.error("Ошибка при перезапуске:", e);
                        }
                    } else {
                        resetButtons();  // Сброс только если не нужно продолжать слушать
                    }
                };
            } else {
                showError("Ваш браузер не поддерживает Web Speech API для автоматической записи.");
            }

            // Клик по кнопке для отправки текста без озвучивания
            $('#text_with_chatgpt').on('click', function () {
                currentButton = 'text_with_chatgpt';
                handleRecordButtonClick(this, currentButton);
            });

            // Клик по второй кнопке
            $('#speak_with_chatgpt').on('click', function () {
                currentButton = 'speak_with_chatgpt';
                handleRecordButtonClick(this, currentButton);
            });

            // Клик по третей кнопке
            $('#speak_with_chatgpt_hd').on('click', function () {
                currentButton = 'speak_with_chatgpt_hd';
                handleRecordButtonClick(this, currentButton);
            });

            // Клик по кнопке "Остановить воспроизведение"
            $('#stop_playback').on('click', function () {
                stopPlayback();
            });

            // Функция для обработки нажатия на кнопку
            function handleRecordButtonClick(button, action) {
                if (isListening) {
                    console.log('Остановка распознавания');
                    recognition.stop();
                    isListening = false;
                    resetButton(button);
                } else {
                    if (recognition) {
                        recognition.lang = $('#spokenLanguageSelect').val();
                        console.log('Запуск распознавания для языка:', recognition.lang);
                        try {
                            if (!isSpeaking) { // Проверка, идет ли озвучивание
                                recognition.start();
                                isListening = true;
                                $(button).data('original-text', $(button).html());
                                $(button).html('<span class="d-flex justify-content-center align-items-center w-100 h-100" style="font-size: 2rem;">🕗</span>');
                            } else {
                                showError('Не могу запустить распознавание, идет озвучивание.');
                            }
                        } catch (e) {
                            showError('Ошибка при запуске распознавания.');
                            console.log('Ошибка при запуске распознавания:', e);
                        }
                    }
                }
            }

            // Отправка распознанного текста на сервер
            function sendTranscriptionToServer(transcription, action) {
                const inputLanguage = $('#spokenLanguageSelect').val();
                const playbackLanguage = $('#languageSelect').val();
                console.log(`Отправка текста на сервер: ${transcription}`);

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
                    success: function (response) {
                        console.log("Результат сервера: ", response);
                        handleServerResponse(response, action);
                    },
                    error: function (xhr, status, error) {
                        showError("Ошибка при отправке данных на сервер.");
                        console.error("Ошибка при отправке данных на сервер: ", error);
                    }
                });
            }

            // Обрабатываем ответ от сервера
            function handleServerResponse(data, button) {
                switch (button) {
                    case 'text_with_chatgpt':
                        appendBotMessage(data.text);
                        isSpeaking = false;
                        break;
                    case 'speak_with_chatgpt':
                        appendBotMessage(data.text);
                        isSpeaking = true; // Устанавливаем, что сейчас идет озвучивание
                        speakText(data.text, button, data.playback_language);
                        break;
                    case 'speak_with_chatgpt_hd':
                        appendBotMessage(data.text);
                        isSpeaking = true; // Устанавливаем, что сейчас идет озвучивание
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
                $('.recordButton').each(function () {
                    resetButton(this);
                });
                isListening = false;
            }

            // Воспроизведение текста с помощью Web Speech API
            function speakText(text, button, lang = 'ru-RU') {
                if(textVoice === text){
                    return;
                }
                if (voices.length === 0) {
                    window.speechSynthesis.onvoiceschanged = function () {
                        voices = window.speechSynthesis.getVoices();
                        speakText(text, button, lang);
                    };
                    return;
                }
                console.log('Начало озвучивания текста:', text);
                textVoice = text;
                const utterance = new SpeechSynthesisUtterance(text);
                let selectedVoice = voices.find(voice => voice.lang === lang) || voices.find(voice => voice.lang.startsWith('ru')) || voices[0];

                if (selectedVoice) {
                    utterance.voice = selectedVoice;
                } else {
                    console.error('Голос не найден.');
                }

                window.speechSynthesis.speak(utterance);
                $('#stop_playback').prop('disabled', false);
                // Отключаем распознавание во время озвучивания
                isSpeaking = true;
                utterance.onend = function () {
                    console.log('Озвучивание завершено');
                    $('#stop_playback').prop('disabled', true); // Деактивируем кнопку после окончания
                    isSpeaking = false; // Включаем обратно распознавание
                    if (isListening && !isSpeaking) { // Проверка состояния перед запуском
                        try {
                            recognition.start();
                        } catch (e) {
                            console.log("Распознавание уже запущено:", e);
                        }
                    }
                };
                utterance.onerror = function(event) {
                    showError('Ошибка при озвучивании.');
                    console.error('Ошибка при озвучивании:', event);
                };
            }

            // Воспроизведение HD-звука
            function speakTextHD(audioUrl, button) {
                console.log('Начало воспроизведения HD-звука:', audioUrl);
                currentAudio = new Audio(audioUrl);
                currentAudio.play();
                $('#stop_playback').prop('disabled', false);
                // Отключаем распознавание во время озвучивания
                isSpeaking = true;
                currentAudio.onended = function () {
                    console.log('Воспроизведение HD-звука завершено');
                    $('#stop_playback').prop('disabled', true); // Деактивируем кнопку после окончания
                    isSpeaking = false; // Включаем обратно распознавание
                    if (isListening && !isSpeaking) { // Проверка состояния перед запуском
                        recognition.start(); // Возобновляем распознавание
                    }
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
                isSpeaking = false;
            }

            // Отслеживание переключения вкладок
            document.addEventListener('visibilitychange', function () {
                if (!document.hidden && isListening) {
                    try {
                        recognition.start();
                    } catch (e) {
                        console.log("Распознавание уже запущено:", e);
                    }
                }
            });
        })
    </script>
@endsection
