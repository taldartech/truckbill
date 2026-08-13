<div class="row g-2">
    <div class="col-md-7">
        <label for="template_name" class="form-label">Business name *</label>
        <input type="text" class="form-control @error('template_name') is-invalid @enderror" id="template_name" name="template_name" required value="{{ old('template_name', $setting->template_name) }}">
        @error('template_name') <div class="text-danger">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-5">
        <label for="template_header" class="form-label">Tagline / header line</label>
        <input type="text" class="form-control @error('template_header') is-invalid @enderror" id="template_header" name="template_header" placeholder="e.g. Transport Contractor" value="{{ old('template_header', $setting->template_header) }}">
        @error('template_header') <div class="text-danger">{{ $message }}</div> @enderror
    </div>
    <div class="col-12">
        <label for="template_address" class="form-label">Address *</label>
        <input type="text" class="form-control @error('template_address') is-invalid @enderror" id="template_address" name="template_address" required value="{{ old('template_address', $setting->template_address) }}">
        @error('template_address') <div class="text-danger">{{ $message }}</div> @enderror
    </div>
    <div class="col-12">
        <label for="template_jurisdictional_boundary" class="form-label">Jurisdiction line</label>
        <input type="text" class="form-control @error('template_jurisdictional_boundary') is-invalid @enderror" id="template_jurisdictional_boundary" name="template_jurisdictional_boundary" placeholder="e.g. Subject to Mumbai jurisdiction" value="{{ old('template_jurisdictional_boundary', $setting->template_jurisdictional_boundary) }}">
        @error('template_jurisdictional_boundary') <div class="text-danger">{{ $message }}</div> @enderror
    </div>
    <div class="col-12">
        <label for="template_note" class="form-label">Footer note</label>
        <textarea class="form-control texteditor @error('template_note') is-invalid @enderror" id="template_note" name="template_note" rows="3">{{ old('template_note', $setting->template_note) }}</textarea>
        @error('template_note') <div class="text-danger">{{ $message }}</div> @enderror
    </div>
</div>
