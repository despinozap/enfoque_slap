import { TestBed } from '@angular/core/testing';

import { CompradoresService } from './compradores.service';

describe('CompradoresService', () => {
  let service: CompradoresService;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(CompradoresService);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
