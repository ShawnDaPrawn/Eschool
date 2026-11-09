// Generated union compatibility for dart-dio 7.25.0.
class PoolMemberPageResponseCode {
  const PoolMemberPageResponseCode._(this.value);

  final Object? value;

  factory PoolMemberPageResponseCode.fromJson(Object? value) {
    if (value != null && value is! int && value is! String) {
      throw FormatException(
        'A response code must be an integer, string or null.',
      );
    }
    return PoolMemberPageResponseCode._(value);
  }

  Object? toJson() => value;

  @override
  bool operator ==(Object other) =>
      other is PoolMemberPageResponseCode && other.value == value;

  @override
  int get hashCode => value.hashCode;
}
