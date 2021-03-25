import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from 'src/environments/environment';
import { Parameter } from '../interfaces/parameter';

@Injectable({
  providedIn: 'root'
})
export class ParametersService {

  constructor(private httpClient: HttpClient) { }

  public getParameter(parameter_id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/parameters/${parameter_id}`;
    
    return this.httpClient.get(endpoint);
  }
  
  public getParameters(): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/parameters`;
    
    return this.httpClient.get(endpoint);
  }

  public updateParameter(parameter_id: number, parameter: Parameter): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/parameters/${parameter_id}`;

    let httpOptions = { 
      method: 'PUT',
      headers:
      {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Access-Control-Allow-Origin': '*',
        'Access-Control-Allow-Methods': 'GET,POST,OPTIONS,DELETE,PUT'
      }
    };
    
    return this.httpClient.put(endpoint, parameter, httpOptions);
  }
}
