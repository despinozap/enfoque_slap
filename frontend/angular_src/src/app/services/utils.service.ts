import { Injectable } from '@angular/core';
import { WorkBook, WorkSheet } from 'xlsx/types';

/* XLSX lib */
import * as XLSX from 'xlsx';
import { Role } from '../interfaces/role';

@Injectable({
  providedIn: 'root'
})
export class UtilsService {

  constructor() { }

  public dateStringFormat(value: string): string {
    return value.substr(0, 10);
  }

  public moneyStringFormat(value: number): string {
    let sValue = value.toString();
    let dotIndex = sValue.indexOf('.');

    let index = (dotIndex >= 0) ? dotIndex : sValue.length; 

    let response = '';
    let counter = 0;
    while(index > 0)
    {
      response = sValue[--index] + response;

      if((++counter === 3) && (index > 0))
      {
        response = ',' + response;
        counter = 0;
      }
    }

    return (dotIndex > 0) ? response + sValue.substring(dotIndex, sValue.length) : response;
  }

  private parseNumberToTwoDigits(n: number): string {
    let strN = n.toString();
    
    return strN.length < 2 ? `0${strN}` : strN;
  }

  public exportTableToExcel(data: any[], title: string): void {
    //Create a new book
    const wb: WorkBook = XLSX.utils.book_new();
    //Convert the table to a sheet
    const ws: WorkSheet = XLSX.utils.aoa_to_sheet(data);
    
    //Add the sheet to the book
    XLSX.utils.book_append_sheet(wb, ws, title);

    let today = new Date();
    let filename = `${today.getFullYear()}${this.parseNumberToTwoDigits(today.getMonth())}${this.parseNumberToTwoDigits(today.getDay())}-${this.parseNumberToTwoDigits(today.getHours())}${this.parseNumberToTwoDigits(today.getMinutes())}${this.parseNumberToTwoDigits(today.getSeconds())}_${title}.xlsx`; 
    //Save file
    XLSX.writeFile(wb, filename);
  }

  public validateInputFile(target: DataTransfer, exts: string[]): string
  {
    //Filter for multiple files
    if(target.files.length === 1)
    {
      //Filter for invalid extension
      let lastDot = target.files[0].name.lastIndexOf('.');
      if((lastDot >= 0) && (lastDot < target.files[0].name.length - 1))
      {
        let ext = target.files[0].name.substring(lastDot + 1, target.files[0].name.length).toLowerCase();

        if(exts.includes(ext))
        {
          // Valid filetype, no validation message
          return '';
        }
        else
        {
          return 'Tipo de archivo no permitido';
        }
      }
      else
      {
        return 'Tipo de archivo invalido';
      }
    }
    else if(target.files.length < 1)
    {
      return 'No se ha cargado correctamente el archivo';
    }
    else(target.files.length > 1)
    {
      return 'No puedes cargar multiples archivos';
    }
  }

  public generateMenu(role_id: number): any {

    //Base menu
    let menu = [
      {
        'title': 'Menu',
        'groups': [
          {
            'title': 'Dashboard',
            'icon': 'bx-home-circle',
            'header': false,
            'route': 'panel'
          }
        ]
      }
    ];

    switch(role_id)
    {

      case 1: { // Administrador

        let roleMenu = {
          'title': 'Modulos',
          'groups': [
            {
              'title': 'Sistema',
              'icon': 'bx-cog',
              'header': true,
              'route': '',
              'items': [
                {
                  'title': 'Parametros de sistema',
                  'route': 'parameters'
                }
              ]
            },
            {
              'title': 'Usuarios',
              'icon': 'bxs-user-detail',
              'header': true,
              'route': '',
              'items': [
                {
                  'title': 'Nuevo usuario',
                  'route': 'usuarios/create'
                },
                {
                  'title': 'Lista de usuarios',
                  'route': 'usuarios'
                }
              ]
            },
            {
              'title': 'Clientes',
              'icon': 'bxs-briefcase-alt',
              'header': true,
              'route': '',
              'items': [
                {
                  'title': 'Nuevo cliente',
                  'route': 'clientes/create'
                },
                {
                  'title': 'Lista de clientes',
                  'route': 'clientes'
                }
              ]
            },
            {
              'title': 'Partes',
              'icon': 'bx-barcode',
              'header': true,
              'route': '',
              'items': [
                {
                  'title': 'Lista de partes',
                  'route': 'partes'
                }
              ]
            },
            {
              'title': 'Solicitudes',
              'icon': 'bx-list-ol',
              'header': true,
              'route': '',
              'items': [
                {
                  'title': 'Nueva solicitud',
                  'route': 'solicitudes/create'
                },
                {
                  'title': 'Lista de solicitudes',
                  'route': 'solicitudes'
                }
              ]
            },
            {
              'title': 'Cotizaciones',
              'icon': 'bx-money',
              'header': true,
              'route': '',
              'items': [
                {
                  'title': 'Lista de cotizaciones',
                  'route': 'cotizaciones'
                }
              ]
            }
          ]
        };

        menu.push(roleMenu);

        break;
      }

      case 2: { // Vendedor

        let roleMenu = {
          'title': 'Modulos',
          'groups': [
            {
              'title': 'Solicitudes',
              'icon': 'bx-list-ol',
              'header': true,
              'route': '',
              'items': [
                {
                  'title': 'Nueva solicitud',
                  'route': 'solicitudes/create'
                },
                {
                  'title': 'Lista de solicitudes',
                  'route': 'solicitudes'
                }
              ]
            }
          ]
        };

        menu.push(roleMenu);

        break;
      }

      default: {

        break;
      }

    }

    return menu;
  }
}
