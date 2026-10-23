//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'set_my_handle_request.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SetMyHandleRequest {
  /// Returns a new [SetMyHandleRequest] instance.
  SetMyHandleRequest({required this.handle});

  @JsonKey(name: r'handle', required: true, includeIfNull: false)
  final String handle;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SetMyHandleRequest && other.handle == handle;

  @override
  int get hashCode => handle.hashCode;

  factory SetMyHandleRequest.fromJson(Map<String, dynamic> json) =>
      _$SetMyHandleRequestFromJson(json);

  Map<String, dynamic> toJson() => _$SetMyHandleRequestToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
