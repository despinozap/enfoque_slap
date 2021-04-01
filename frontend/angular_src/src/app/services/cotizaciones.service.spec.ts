import { TestBed } from '@angular/core/testing';

import { CotizacionesService } from './cotizaciones.service';

describe('CotizacionesService', () => {
  let service: CotizacionesService;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(CotizacionesService);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
