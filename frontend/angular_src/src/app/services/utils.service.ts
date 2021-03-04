import { Injectable } from '@angular/core';

@Injectable({
  providedIn: 'root'
})
export class UtilsService {

  constructor() { }

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
