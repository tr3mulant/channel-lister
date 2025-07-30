<x-channel-lister::layout>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h1>Create New Channel Lister Field</h1>
                    <a href="{{ route('channel-lister-field.index') }}" class="btn btn-secondary">
                        Back to List
                    </a>
                </div>

                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('channel-lister-field.store') }}" method="POST">
                            @csrf

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-control mb-3">
                                        <label for="field_name" class="form-label">Field Name <span
                                                class="text-danger">*</span></label>
                                        <input type="text"
                                            class="form-control @error('field_name') is-invalid @enderror"
                                            id="field_name" name="field_name" value="{{ old('field_name') }}"
                                            placeholder="e.g., product_title" required>
                                        @error('field_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="form-text text-muted">The technical field name (no spaces, use
                                            underscores)</small>
                                    </div>

                                    <div class="form-control mb-3">
                                        <label for="display_name" class="form-label">Display Name <span
                                                class="text-danger">*</span></label>
                                        <input type="text"
                                            class="form-control @error('display_name') is-invalid @enderror"
                                            id="display_name" name="display_name" value="{{ old('display_name') }}"
                                            placeholder="e.g., Product Title" required>
                                        @error('display_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="form-text text-muted">The name shown to users</small>
                                    </div>

                                    <div class="form-control mb-3">
                                        <label for="marketplace" class="form-label">Marketplace <span
                                                class="text-danger">*</span></label>
                                        <input type="text"
                                            class="form-control @error('marketplace') is-invalid @enderror"
                                            id="marketplace" name="marketplace" value="{{ old('marketplace') }}"
                                            placeholder="e.g., Amazon, eBay, Etsy" required>
                                        @error('marketplace')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-control mb-3">
                                        <label for="input_type" class="form-label">Input Type <span
                                                class="text-danger">*</span></label>
                                        <select class="form-control @error('input_type') is-invalid @enderror"
                                            id="input_type" name="input_type" required>
                                            <option value="">Select Input Type</option>
                                            @foreach (\IGE\ChannelLister\Enums\InputType::cases() as $inputType)
                                                <option value="{{ $inputType->value }}"
                                                    {{ old('input_type') == $inputType->value ? 'selected' : '' }}>
                                                    {{ ucfirst($inputType->value) }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('input_type')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-control mb-3">
                                        <label for="type" class="form-label">Type <span
                                                class="text-danger">*</span></label>
                                        <select class="form-control @error('type') is-invalid @enderror" id="type"
                                            name="type" required>
                                            <option value="">Select Type</option>
                                            @foreach (\IGE\ChannelLister\Enums\Type::cases() as $type)
                                                <option value="{{ $type->value }}"
                                                    {{ old('type') == $type->value ? 'selected' : '' }}>
                                                    {{ ucfirst($type->value) }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('type')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-control mb-3">
                                        <label for="ordering" class="form-label">Ordering</label>
                                        <input type="number"
                                            class="form-control @error('ordering') is-invalid @enderror" id="ordering"
                                            name="ordering" value="{{ old('ordering', 0) }}" min="0">
                                        @error('ordering')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="form-text text-muted">Display order (0 = first)</small>
                                    </div>

                                    <div class="form-control mb-3">
                                        <div class="form-check">
                                            <input type="checkbox"
                                                class="form-check-input @error('required') is-invalid @enderror"
                                                id="required" name="required" value="1"
                                                {{ old('required') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="required">
                                                Required Field
                                            </label>
                                            @error('required')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="form-control mb-3">
                                        <label for="grouping" class="form-label">Grouping</label>
                                        <input type="text"
                                            class="form-control @error('grouping') is-invalid @enderror" id="grouping"
                                            name="grouping" value="{{ old('grouping') }}"
                                            placeholder="e.g., Basic Info, Pricing">
                                        @error('grouping')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="form-text text-muted">Group related fields together</small>
                                    </div>

                                    <div class="form-control mb-3">
                                        <label for="tooltip" class="form-label">Tooltip</label>
                                        <textarea class="form-control @error('tooltip') is-invalid @enderror" id="tooltip" name="tooltip"
                                            placeholder="e.g., Enter the product title">{{ old('tooltip') }}</textarea>
                                        @error('tooltip')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="form-text text-muted">Provide additional information about the
                                            field</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-control mb-3">
                                        <label for="default_value" class="form-label">Default Value</label>
                                        <textarea class="form-control @error('default_value') is-invalid @enderror" id="default_value" name="default_value"
                                            rows="3" placeholder="Default value for this field">{{ old('default_value') }}</textarea>
                                        @error('default_value')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-control mb-3">
                                        <label for="field_value" class="form-label">Field Value</label>
                                        <textarea class="form-control @error('field_value') is-invalid @enderror" id="field_value" name="field_value"
                                            rows="3" placeholder="Current field value">{{ old('field_value') }}</textarea>
                                        @error('field_value')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-control mb-3">
                                        <label for="extra_json" class="form-label">Extra JSON</label>
                                        <textarea class="form-control @error('extra_json') is-invalid @enderror" id="extra_json" name="extra_json"
                                            rows="5" placeholder='{"key": "value"}'>{{ old('extra_json') }}</textarea>
                                        @error('extra_json')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="form-text text-muted">Additional configuration as JSON</small>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="{{ route('channel-lister-field.index') }}" class="btn btn-secondary">
                                    Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    Create Field
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-channel-lister::layout>
