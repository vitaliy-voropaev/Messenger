var Messenger = {
	
	config: {

		selectors: {
			dialog: '#dialog',
			messages: '.dialog-messages',
			message: '.message',
			form: 'form.dialog-send',
			form_message: '#message',
			dialog_row: '.dialog-row',
			dialog_create_form: '#messenger_dialog_create',
			dialog_create_btn: '.create-dialog',
			dialog_create_users: '#users',
			dialog_create_name: '.create-dialog-name',
			notifications: '#messenger_notifications'
		},

		push: MessengerConfig.push_id,
		is_messenger: $("div").is('#messenger')
	},

	initialize: function() {

		if(!jQuery().jGrowl) {
			document.write('<script src="'+MessengerConfig.jsUrl+'lib/jquery.jgrowl.min.js"><\/script>');
		}

		if (!jQuery().niceScroll) {
			document.write('<script src="' + MessengerConfig.jsUrl + 'lib/jquery.nicescroll.min.js"><\/script>');
		}

		if (!jQuery().select2) {
			document.write('<script src="' + MessengerConfig.jsUrl + 'lib/jquery.select2.min.js"><\/script>');
			document.write('<link type="text/css" rel="stylesheet" href="' + MessengerConfig.cssUrl + 'lib/select2.min.css">');
		}

		if (!jQuery().tinysort) {
			document.write('<script src="'+ MessengerConfig.jsUrl + 'lib/tinysort.min.js"><\/script>');
		}

		// отправка сообщения
		$(document).on('submit', Messenger.config.selectors.form, function(e) {
			e.preventDefault();
			Messenger.dialog.message.send(this);
		});

		// функционал "пишет вам сообщение"
		$(document).on('keyup', 'input[name="message"]', function() {
            var startTime;
            TimeId = undefined;

            return function(e)
            {
                if(TimeId !== undefined) {
                    clearTimeout(TimeId);
                    TimeId = undefined;
                } else {
                    CometServer().web_pipe_send("web_dialog_"+MessengerConfig.dialog, "typing", {
                        dialog: MessengerConfig.dialog,
                        user_id: MessengerConfig.user_id,
                        user_name: MessengerConfig.user_name
                    });
                }

                TimeId = setTimeout(function() {
                    // Отправка сообщения об окончании набора
                    CometServer().web_pipe_send("web_dialog_"+MessengerConfig.dialog, "typingOff", {
                        dialog: MessengerConfig.dialog,
                        user_id: MessengerConfig.user_id,
                        user_name: MessengerConfig.user_name
                    });
                    
                    TimeId = undefined;

                }, 300);
             }

		} ());

		// смена диалога
		$(document).on('click', Messenger.config.selectors.dialog_row, function(e) {
			e.preventDefault();
			Messenger.dialog.change($(this).data('dialog'));
			return false;
		});

		// создание диалога (обертка)
		$(document).on('click', Messenger.config.selectors.dialog_create_btn, function(e) {
			Messenger.dialog.create.wrapper();
			e.preventDefault();
			return false;
		});

		// создание диалога (сабмит)
		$(document).on('submit', Messenger.config.selectors.dialog_create_form, function(e) {
			Messenger.dialog.create.submit();
			e.preventDefault();
			return false;
		});

		$(document).ready(function() {
			CometServer().start({dev_id:Messenger.config.push, user_id:MessengerConfig.user_id, user_key:MessengerConfig.user_hash});
			CometServer().sendStatistics("modx-messenger", 0.01, '' );
			CometServer().subscription("msg", function(event){	Messenger.push(event.data) });
			
			if (Messenger.config.is_messenger) {
				$('.dialogues .users').niceScroll();

				// подписка на каналы всех диалогов для вывода "%username% набирает сообщение"
				var dialogues = $(".dialog-row").map(function() { return $(this).data("dialog"); }).get();

				$.each( dialogues, function(key, value) {
					CometServer().subscription("web_dialog_"+value+".typing", function(event){ Messenger.dialog.typing(event.data); });
					CometServer().subscription("web_dialog_"+value+".typingOff", function(event) {
                        Messenger.dialog.typingOff(event.data);
                    });
				});
			}
		});

	},

	push: function(data) {
		console.log(data);

		// определение страницы с открытым мессенджером
		if (Messenger.config.is_messenger) {

			Messenger.dialog.lastMessage(data.dialog, data.message, data.timestamp);
			Messenger.dialog.counter(data.dialog, 'plus', 1);
			
			if (data.dialog = MessengerConfig.dialog) 
				Messenger.dialog.message.load('new');
		
		} else {

			$.jGrowl(data.message, {header: 'Сообщение от ' + data.from_name});
		}

		Messenger.notifications('plus', 1);
	},

	notifications: function(action, number) {
		
		var notifications = $(Messenger.config.selectors.notifications);
		var counter = parseFloat(notifications.text());

		if(action == 'plus') notifications.text( counter + number);
		if(action == 'minus') notifications.text( counter - number);
		
		// новое значение счетчика
		counter = parseFloat(notifications.text());

		if (counter > 0) {
			notifications.removeClass('empty');
		} else {
			notifications.addClass('empty').text(0);
		}
	},

	dialogues: {

		load: function() {
			console.log('загрузка диалогов');

			$.post(MessengerConfig.actionUrl, { action: 'dialogues/load', dialog: MessengerConfig.dialog }, function(response) {
				
				if (response.success) {
					$('.dialogues .users').html('').html(response.dialogues);
					$('.dialogues .users').niceScroll();
				}

			},'json');
		}
	}

	,dialog: {

		load: function(dialog_id) {

			$.post(MessengerConfig.actionUrl, {action: 'dialogues/load', id: dialog_id}, function(response) {
				
				if(response.success)
					$('#messenger .dialogues .users').prepend(response.html);
				
			},'json');
		},

		create: {
			// загрузка формы для создания диалога
			wrapper: function() {
				
				$(Messenger.config.selectors.dialog_row).removeClass('active');
				MessengerConfig.dialog = 0;

				$.post(MessengerConfig.actionUrl, { action: 'dialog/new', type: 'wrapper'}, function(response) {
					
					if (response.success) {
						$(Messenger.config.selectors.dialog).html('').html(response.html);

						$(Messenger.config.selectors.dialog_create_users).select2({
					        placeholder: 'Введите имя получателя',
							tags: true,
							tokenSeparators: [",", " "],
							multiple: true,
							minimumInputLength: 3,

							templateResult: function(user) {

								if (!user.id) { return user.text; }
								
								var photo = '<img src="'+ user.photo + '" />';
								if (!user.photo) photo = user.text.slice(0,1);
								
								var $user = $(
								    '<span class="messenger-user-row"><span class="avatar">' + photo + '</span><span class="name">' + user.text + '</span></span>'
								);
								
								return $user;
							},

							createTag: function(params) {
				                return undefined;
				           	},

					        ajax: {
					            url: MessengerConfig.actionUrl,
					            dataType: 'json',
								delay: 250,
								quietMillis: 100,
					            data: function (params) {
					                return {
										action: 'dialog/new/users',
					                    search: params.term
					                };
					            },
								results: function (data, page) {
					                return { results: data.results };
					            }
							},

							escapeMarkup: function (markup) { return markup; }
					    });

						$(Messenger.config.selectors.dialog_create_users).on("change", function (e) {
							var dialog_subject = $(Messenger.config.selectors.dialog_create_name);
							
							if ($(this).val().length > 1) {
								dialog_subject.show();
							} else {
								dialog_subject.hide();
							}
						});

						$(Messenger.config.selectors.dialog_create_btn).addClass('active');
					}

				},'json');
			},

			submit: function() {
				var form = $('#messenger_dialog_create');

				var data = {
					action: 'dialog/new',
					type: 'new',
					name: form.find('input[name="name"]').val(),
					users: form.find('select[name="users"]').val(),
					message: form.find('textarea[name="message"]').val()
				};

				$.post(MessengerConfig.actionUrl, data, function(response) {
					if (response.success) {
						Messenger.dialog.load(response.dialog);
						Messenger.dialog.change(response.dialog);
					}
				},'json');
			}
		},

		// dialog counter in dialog list
		counter: function(dialog_id, action, number) {

			var dialog = $('.dialog-row[data-dialog="'+dialog_id+'"]');
			var counter = parseFloat(dialog.find('.unread').text());
			
			if (action == 'plus') 
				dialog.find('.unread').text( counter + number);
				
			if (action == 'minus') 
				dialog.find('.unread').text( counter - number);

			if (parseFloat(dialog.find('.unread').text()) > 0) { 
				dialog.addClass('unread'); 
			} else { 
				dialog.removeClass('unread'); 
			}
		},

		// %username% writing system...
		typing: function(data) {

			// if dialog selected
			if (MessengerConfig.dialog == data.dialog) {
				
				$('.typing-block').html(data.user_name+' набирает сообщение...');

				setTimeout(function() {
					$('.typing-block').html('');
				}, 5000);
			} else {
                
                // if selected another dialog, typing dots on the avatar of dialog
				var dialog = $('.dialog-row[data-dialog="'+data.dialog+'"]');
				dialog.addClass('typing');

				setTimeout(function(){
					dialog.removeClass('typing');
				}, 5000);
			}

            //$('.dialog-row[data-dialog="'+data.dialog+'"] .avatar').addClass('typing');
		},

		typingOff: function(data) {

			// if dialog selected
			if(MessengerConfig.dialog == data.dialog)
            {
				$('.typing-block').html('');

			} else {
                // if selected another dialog, typing dots on the avatar of dialog
				var dialog = $('.dialog-row[data-dialog="'+data.dialog+'"]');
				dialog.removeClass('typing');
			}

            //$('.dialog-row[data-dialog="'+data.dialog+'"] .avatar').addClass('typing');
		},

		// insert last message to dialog in list
		lastMessage: function(dialog_id, message, time) {
			var dialog = Messenger.utils.getDialog(dialog_id);
			
			if (dialog) {
				
				dialog.find('.last').text(message);
				dialog.attr('data-date', time);
				
				Messenger.dialog.sort();
			}
		},

		// sorting dialogues
		sort: function() {
			tinysort('#messenger .users>div.dialog-row', {data:'date', order: 'desc'});
		},

		message: {

			// load messages, history or new
			load: function(type) {
				
				if (MessengerConfig.dialog == 0) return;

				var data = {
					action: 'message/load',
					dialog: MessengerConfig.dialog
				};

				if (type == 'history') {
					data.type = 'first';
					data.message_id = Messenger.utils.first();
				}

				if (type == 'new') {
					data.type = 'last';
					data.message_id = Messenger.utils.last();
				}

				$.post(MessengerConfig.actionUrl, data, function(response) {

					if (!response.dialog && type == 'history')
						$(Messenger.config.selectors.messages)
							.addClass('history_full_load')
							.prepend('<div class="note">Вся история переписки загружена</div>');
							
					Messenger.dialog.message.insert(data.type, data.message_id, response.dialog);

				}, 'json');
			},

			// reading new messages, remove highlighting after 1.5 sec
			read: function() {

				setTimeout(function() {
					// find unread messages, data-unread="1", return array
					var messages = $(".message[data-unread='1']").map(function() { return $(this).data("id"); }).get();
					
					if (messages) {
						$.post(MessengerConfig.actionUrl, {action: 'message/read', dialog: MessengerConfig.dialog, messages: messages}, function(response) {
							console.log(response);
							
							if (response.success) {
								if (Messenger.config.is_messenger) {

									// if opened dialog = read messages
									if (MessengerConfig.dialog == response.dialog) {
										$.each( response.messages.split(','), function(key, value) {
											$('.message[data-id="'+value+'"]').attr('data-unread', 0);
										});
									}

									var dialog = $('.dialog-row[data-dialog="'+response.dialog+'"]');
									var dialog_counter = dialog.find('.unread');

									dialog_counter.text( dialog_counter.text() - response.count);
									
									if(parseFloat(dialog_counter.text()) < 1) dialog.removeClass('unread');
								}

								Messenger.notifications('minus', response.count);
							}
						},'json');
					}
				}, 1500);
			},

			// send message & load new messages in dialog
			send: function() {

				var data = {
					action: 'message/send',
					dialog: MessengerConfig.dialog,
					message: $(Messenger.config.selectors.form).find('input[name="message"]').val()
				};

				// if message empty => return;
				if (data.message.length < 1) return;
				$.post(MessengerConfig.actionUrl, data, function(response) {
					console.log(response);
					if (response.success) {
						$(Messenger.config.selectors.form).find('input').val('');
						Messenger.dialog.message.load('new');
						Messenger.dialog.lastMessage(response.data.dialog, response.data.message, response.data.timestamp);
					}
				},'json');

			},

			// insert messages in dialog
			insert: function(type, id, html) {
				
				var messages = $('.dialog-messages');
				var old_height = (messages[0].scrollHeight - messages.scrollTop());
				var message = $(Messenger.config.selectors.messages).find('.message[data-id="'+id+'"]');
				
				if (type == 'first') message.before(html);
				if (type == 'last') message.after(html);
				
				$('.dialog-messages').scrollTop($('.dialog-messages')[0].scrollHeight - old_height);

				Messenger.dialog.message.read();
			}
		},

		// change dialog
		change: function(dialog_id) {
			
			$('.dialogues *.active').removeClass('active');
			
			var dialog = $(Messenger.config.selectors.dialog_row+'[data-dialog="'+dialog_id+'"]');
			MessengerConfig.dialog = dialog_id;
			
			$.post(MessengerConfig.actionUrl, {action: 'dialog/load', dialog: dialog_id}, function(response) {
				if(response.success) {
					$(Messenger.config.selectors.dialog).html('').html(response.template);
					$(Messenger.config.selectors.form_message).focus();
					dialog.addClass('active');
					Messenger.dialog.scroll();
					Messenger.dialog.message.read();
				}
			},'json');
		},

		scroll: function() {

			$(Messenger.config.selectors.messages).scroll(function() {
				
				if ($(this).hasClass('history_full_load')) return;

				if ($(Messenger.config.selectors.messages).scrollTop() == 0)
					Messenger.dialog.message.load('history');
			});

			var height = $(Messenger.config.selectors.messages)[0].scrollHeight;
			$(Messenger.config.selectors.messages).scrollTop(height);
		}
	},

	utils: {
		first: function() {
			return $(Messenger.config.selectors.messages).find(Messenger.config.selectors.message).first().data('id');
		},

		last: function() {
			return $(Messenger.config.selectors.messages).find(Messenger.config.selectors.message).last().data('id');
		},

		getDialog: function(dialog_id) {

			if( $('.dialog-row').is('[data-dialog="'+dialog_id+'"]') ) {
				var dialog = $('.dialog-row[data-dialog="'+dialog_id+'"]');
			} else {
				// если нет диалога  подгружаем
				var dialog = Messenger.dialog.load(dialog_id);
			}

			return dialog;
		}
	}
};

Messenger.Message = {
	success: function(message) {
		if (message) $.jGrowl(message, {theme: 'tickets-message-success'});
	},

	error: function(message) {
		if (message) $.jGrowl(message, {theme: 'tickets-message-error'/*, sticky: true*/});
	},

	info: function(message) {
		if (message) $.jGrowl(message, {theme: 'tickets-message-info'});
	},

	close: function() {
		$.jGrowl('close');
	}
};

Messenger.initialize();

