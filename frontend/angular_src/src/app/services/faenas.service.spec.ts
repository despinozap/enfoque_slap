import { TestBed } from '@angular/core/testing';

import { FaenasService } from './faenas.service';

describe('FaenasService', () => {
  let service: FaenasService;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(FaenasService);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
