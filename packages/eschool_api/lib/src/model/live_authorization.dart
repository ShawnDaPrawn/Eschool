//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'live_authorization.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class LiveAuthorization {
  /// Returns a new [LiveAuthorization] instance.
  LiveAuthorization({required this.auth});

  @JsonKey(name: r'auth', required: true, includeIfNull: false)
  final String auth;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LiveAuthorization && other.auth == auth;

  @override
  int get hashCode => auth.hashCode;

  factory LiveAuthorization.fromJson(Map<String, dynamic> json) =>
      _$LiveAuthorizationFromJson(json);

  Map<String, dynamic> toJson() => _$LiveAuthorizationToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
