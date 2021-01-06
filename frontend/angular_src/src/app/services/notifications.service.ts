import { Injectable } from '@angular/core';

/* SweetAlert2 */
const Swal = require('../../assets/vendors/sweetalert2/sweetalert2.all.min.js');


@Injectable({
    providedIn: 'root'
})
export class NotificationsService {

    constructor() { }

    public static messageType = {
        success: 0,
        error: 1,
        warning: 2,
        info: 3,
        question: 4
    };

    public static showToast(text: string, type: number) {
        let title = '';
        let icon = '';

        switch (type) {
            case 0: {
                title = 'Success!';
                icon = 'success';

                break;
            }

            case 1: {
                title = 'Oops...';
                icon = 'error';

                break;
            }

            case 2: {
                title = 'Hmmm...';
                icon = 'warning';

                break;
            }

            case 3: {
                title = 'Information';
                icon = 'info';

                break;
            }

            case 4: {
                title = 'Question';
                icon = 'question';

                break;
            }

            default: {

                break;
            }
        }

        Swal.fire({
            position: 'top-end',
            icon: icon,
            title: title,
            text: text,
            showConfirmButton: false,
            timer: 2500
        });
    }

    public static showAlert(text: string, type: number) {
        let title = '';
        let icon = '';

        switch (type) {
            case 0: {
                title = 'Success!';
                icon = 'success';

                break;
            }

            case 1: {
                title = 'Oops..';
                icon = 'error';

                break;
            }

            case 2: {
                title = 'Warning!';
                icon = 'warning';

                break;
            }

            case 3: {
                title = 'Information';
                icon = 'info';

                break;
            }

            case 4: {
                title = 'Question';
                icon = 'question';

                break;
            }

            default: {

                break;
            }
        }

        Swal.fire({
            icon: icon,
            title: title,
            text: text,
        });
    }
}
