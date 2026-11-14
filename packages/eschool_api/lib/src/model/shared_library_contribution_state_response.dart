//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/shared_library_contribution_state.dart';
import 'package:json_annotation/json_annotation.dart';

part 'shared_library_contribution_state_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SharedLibraryContributionStateResponse {
  /// Returns a new [SharedLibraryContributionStateResponse] instance.
  SharedLibraryContributionStateResponse({
    required this.error,

    required this.message,

    required this.data,

    required this.code,
  });

  @JsonKey(name: r'error', required: true, includeIfNull: false)
  final bool error;

  @JsonKey(name: r'message', required: true, includeIfNull: false)
  final String message;

  @JsonKey(name: r'data', required: true, includeIfNull: false)
  final SharedLibraryContributionState data;

  @JsonKey(name: r'code', required: true, includeIfNull: false)
  final int code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SharedLibraryContributionStateResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory SharedLibraryContributionStateResponse.fromJson(
    Map<String, dynamic> json,
  ) => _$SharedLibraryContributionStateResponseFromJson(json);

  Map<String, dynamic> toJson() =>
      _$SharedLibraryContributionStateResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
