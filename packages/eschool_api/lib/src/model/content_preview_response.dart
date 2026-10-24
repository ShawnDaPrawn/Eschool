//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/content_preview.dart';
import 'package:json_annotation/json_annotation.dart';

part 'content_preview_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ContentPreviewResponse {
  /// Returns a new [ContentPreviewResponse] instance.
  ContentPreviewResponse({
    required this.error,

    required this.message,

    required this.code,

    required this.data,
  });

  @JsonKey(name: r'error', required: true, includeIfNull: false)
  final bool error;

  @JsonKey(name: r'message', required: true, includeIfNull: false)
  final String message;

  @JsonKey(name: r'code', required: true, includeIfNull: false)
  final int code;

  @JsonKey(name: r'data', required: true, includeIfNull: false)
  final ContentPreview data;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ContentPreviewResponse &&
          other.error == error &&
          other.message == message &&
          other.code == code &&
          other.data == data;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + code.hashCode + data.hashCode;

  factory ContentPreviewResponse.fromJson(Map<String, dynamic> json) =>
      _$ContentPreviewResponseFromJson(json);

  Map<String, dynamic> toJson() => _$ContentPreviewResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
