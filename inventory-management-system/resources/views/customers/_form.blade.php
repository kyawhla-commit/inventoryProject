@csrf
<div class="form-group">
    <label for="name">Name</label>
    <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $customer->name ?? '') }}" required>
</div>
<div class="form-group">
    <label for="email">Email</label>
    <input type="email" name="email" id="email" class="form-control" value="{{ old('email', $customer->email ?? '') }}">
</div>
<div class="form-group">
    <label for="phone">Phone</label>
    <input type="text" name="phone" id="phone" class="form-control" value="{{ old('phone', $customer->phone ?? '') }}">
</div>
<div class="form-group">
    <label for="address">Address</label>
    <textarea name="address" id="address" class="form-control">{{ old('address', $customer->address ?? '') }}</textarea>
</div>
<button type="submit" class="btn btn-primary mt-3">Submit</button>
