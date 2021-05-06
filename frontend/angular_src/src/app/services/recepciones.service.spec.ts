import { TestBed } from '@angular/core/testing';

import { RecepcionesService } from './recepciones.service';

describe('RecepcionesService', () => {
  let service: RecepcionesService;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(RecepcionesService);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
