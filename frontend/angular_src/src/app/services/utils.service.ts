import { Injectable } from '@angular/core';
import { WorkBook, WorkSheet } from 'xlsx/types';

/* XLSX lib */
import * as XLSX from 'xlsx';

@Injectable({
  providedIn: 'root'
})
export class UtilsService {

  constructor() { }

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
    XLSX.writeFile(wb, `${filename}.xlsx`);
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
}
