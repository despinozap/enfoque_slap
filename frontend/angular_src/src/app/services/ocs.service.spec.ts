import { TestBed } from '@angular/core/testing';

import { OcsService } from './ocs.service';

describe('OcsService', () => {
  let service: OcsService;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(OcsService);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
