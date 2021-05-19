import { TestBed } from '@angular/core/testing';

import { DespachosService } from './despachos.service';

describe('DespachosService', () => {
  let service: DespachosService;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(DespachosService);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
