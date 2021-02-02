import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { UsersService } from 'src/app/services/users.service';

@Component({
  selector: 'app-edit',
  templateUrl: './edit.component.html',
  styleUrls: ['./edit.component.css']
})
export class UsuariosEditComponent implements OnInit {

  loading: boolean;
  private sub: any;
  private id: number;
  responseErrors: any;

  constructor(
    private route: ActivatedRoute, 
    private _usersService: UsersService,
    private router: Router
  ) 
  {
    this.loading = false;
    this.id = -1;
    this.responseErrors = [];
  }

  ngOnInit(): void {
    /*
    this.sub = this.route.params.subscribe(params => {
      
      this.id = params['id'];
      this.vendorForm.disable();
      this.loading = true;

      this._usersService.getUser(this.id)
      .subscribe(
        //Success request
        (response: any) => {
          this.vendorForm.enable();
          this.loading = false;
          
          this.loadFormData(response.data);
        },
        //Error request
        (errorResponse: any) => {

          switch(errorResponse.status)
          {
          
            case 412: //Object not found
            {
              NotificationsService.showToast(
                errorResponse.error.message,
                NotificationsService.messageType.warning
              );

              break;
            }
          
            default: //Unhandled error
            {
              NotificationsService.showToast(
                'Fail on loading the vendor',
                NotificationsService.messageType.error
              );
    
              break;

            }
          }

          this.loading = false;
          this.goTo_vendorsList();
        }
      );
    });
    */
  }

}
