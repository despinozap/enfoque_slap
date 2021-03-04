import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from 'src/environments/environment';

@Injectable({
  providedIn: 'root'
})
export class MarcasService {

  constructor(private httpClient: HttpClient) { }

  public getMarcas(): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/marcas/all`;
    
    return this.httpClient.get(endpoint);
  }
}
