import { Injectable } from '@angular/core';

@Injectable({
  providedIn: 'root'
})
export class DatatablesService {

  constructor() { }

  public initDataTables(id: string){

    // let code = `$("#${id}").DataTable({lengthChange:!1,buttons:["copy","excel","pdf","colvis"]}).buttons().container().appendTo("#${id}_wrapper .col-md-6:eq(0)");`;
    let code = `$("#${id}").DataTable().destroy();$("#${id}").DataTable({lengthChange:!1,buttons:["copy","excel","pdf","colvis"]}).buttons().container().appendTo("#${id}_wrapper .col-md-6:eq(0)");`;
    
    this.injectDataTables(code);
  }

  private injectDataTables(code: string): void
  {
    var script = document.createElement("script");
    script.append(code);
    document.body.appendChild(script); //script injection
  }
}
