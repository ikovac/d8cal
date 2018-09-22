(function ($, drupalSettings) {
    'use strict';

    //let reserved_dates = [];
    let reserved_dates = drupalSettings.dates;
    let nid = drupalSettings.nid;
    let content_owner = drupalSettings.content_owner;

    $(document).ready(function () {
        let selectedDate = null;
        const dateNote = document.getElementById('dateNote');
        const available = document.getElementById('available');
        const unavailable = document.getElementById('unavailable');
        const clrBtn = document.getElementById('clearBtn');
        const saveBtn = document.getElementById('saveDateBtn');
        const interactivDiv = $('#interactivDiv');
        const flashMessageDiv = $('#flash-message');
        flashMessageDiv.hide();

        // kada se klikne clear, makni interactivDiv.
        clrBtn.addEventListener('click', onClrBtn);

        // dodaj event listener na saveBtn
        saveBtn.addEventListener('click', () => {
            let obj = {
                reserved: unavailable.checked,
                date: selectedDate,
                note: dateNote.value,
                nid: nid,
                content_owner: content_owner
            };
            onSaveBtn(obj);
        });


        var options = {
            width: 320,
            height: 320,
            selectedRang: [new Date(), null],
            data: reserved_dates,
            date: new Date(),
            //monthArray: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
            onSelected: onSelectedFunction
        };

        var cl = $('#calendar');
        cl.calendar(options);

        function onSelectedFunction(view, date, note) {
            let year = date.getFullYear();
            let month = date.getMonth() + 1;
            let day = date.getDate();

            if(month < 10) {
                month = '0' + month;
            }

            if(day < 10) {
                day = '0' + day;
            }

            let string_date = year + '-' + month + '-' + day;
            selectedDate = string_date;

            // Prikazi interactivDiv
            interactivDiv.show();
            interactivDiv.css('display', 'inline-block');

            // Postavi date field na odabrani datum.
            const dataField = $('#selectedDate');
            dataField.val(string_date);

            // Popuni note polje ukoliko postoji note za taj datum, u protivnom stavi prazno.
            if(note == ' ') {
                dateNote.value = '';
            } else {
                dateNote.value = note || '';
            }


            // Checkiraj available || unavailable ovisno je li datum postoji u bazi.
            if(ifDateExists(string_date)) {
                unavailable.checked = true;
            } else {
                available.checked = true;
            }
        } // end of onSelectedFunction

        function onClrBtn() {
            interactivDiv.hide();
            selectedDate = null;
            let args = cl.calendar('getDisDateValue');
            cl.calendar('updateDateView', args[0], args[1]);
        }

        function onSaveBtn(obj) {
            if(ifDateExists(obj.date)) {
                if(obj.reserved) {
                    // put request, update note.
                    $.ajax({
                        type: 'PUT',
                        url: '/reservation_calendar/api/update',
                        contentType: 'application/json',
                        data: JSON.stringify(obj),
                        success: function(res){
                            // prvo izbacit postojeći pa dodat novi
                            if(res.status) {
                                let objIndex = reserved_dates.findIndex((selected_obj => selected_obj.date == obj.date));
                                reserved_dates[objIndex].value = obj.note;
                                let args = cl.calendar('getDisDateValue');
                                cl.calendar('updateDateView', args[0], args[1]);
                                successMessage('Rezervacija na datum: ' + obj.date + ' je izmijenjena.');
                            } else {
                                errorMessage();
                                console.log(res.msg);
                            }
                        }
                    });
                } else {
                    // delete request, date exist and reserved is false
                    $.ajax({
                        type: 'DELETE',
                        url: '/reservation_calendar/api/delete',
                        contentType: 'application/json',
                        data: JSON.stringify(obj),
                        success: function(res){
                            if(res.status) {
                                let objIndex = reserved_dates.findIndex((selected_obj => selected_obj.date == obj.date));
                                reserved_dates.splice(objIndex, 1);
                                let args = cl.calendar('getDisDateValue');
                                cl.calendar('updateDateView', args[0], args[1]);
                                successMessage('Otkazana je rezervacija za datum: ' + obj.date + '.');
                            } else {
                                errorMessage();
                                console.log(res.msg);
                            }
                        }
                    });
                }
            } else {
                if(obj.reserved) {
                    // post request, add newly reserved date.
                    $.ajax({
                        type: 'POST',
                        url: '/reservation_calendar/api/add',
                        contentType: 'application/json',
                        data: JSON.stringify(obj),
                        success: function(res){
                            if(res.status) {
                                reserved_dates.push({date: obj.date, value: obj.note});
                                // args[0] = crnt year, args[1] = crnt month, zatim poziv funkcije updateDateView koji refresha trenutni view
                                // sa novim rezerviranim datumom ili promjenama...
                                let args = cl.calendar('getDisDateValue');
                                cl.calendar('updateDateView', args[0], args[1]);
                                successMessage('Datum: ' + obj.date + ' je uspješno rezerviran.');
                            } else {
                                errorMessage();
                                console.log(res.msg);
                            }
                        }
                    });
                }
            }
            onClrBtn();
        } // end of onSaveBtn function.

        function ifDateExists(date) {
            return reserved_dates.find((element) => {
                return element.date == date;
            });
        }

        function successMessage(message) {
            flashMessageDiv.append('<p>' + message + '</p>');
            flashMessageDiv.css('background-color', '#9dde9d');
            flashMessageDiv.show();
            setTimeout(() => {
                flashMessageDiv.empty();
                flashMessageDiv.hide();
            }, 4000);
        }
        function errorMessage() {
            flashMessageDiv.append("<p>Došlo je do pogreške, molimo pokušajte kasnije. Ukoliko se problem ponovi, kontaktirajte administratora.</p>");
            flashMessageDiv.css('background-color', '#ff1717');
            flashMessageDiv.show();
            setTimeout(() => {
                flashMessageDiv.empty();
                flashMessageDiv.hide();
            }, 10000);
        }

    }); //end of document.ready
})(jQuery, drupalSettings);