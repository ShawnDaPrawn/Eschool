//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/text_messaging_response_data_number.dart';
import 'package:eschool_api/src/model/text_messaging_response_data_notices_inner.dart';
import 'package:json_annotation/json_annotation.dart';

part 'text_messaging_response_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TextMessagingResponseData {
  /// Returns a new [TextMessagingResponseData] instance.
  TextMessagingResponseData({required this.number, required this.notices});

  @JsonKey(name: r'number', required: true, includeIfNull: false)
  final TextMessagingResponseDataNumber number;

  @JsonKey(name: r'notices', required: true, includeIfNull: false)
  final List<TextMessagingResponseDataNoticesInner> notices;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TextMessagingResponseData &&
          other.number == number &&
          other.notices == notices;

  @override
  int get hashCode => number.hashCode + notices.hashCode;

  factory TextMessagingResponseData.fromJson(Map<String, dynamic> json) =>
      _$TextMessagingResponseDataFromJson(json);

  Map<String, dynamic> toJson() => _$TextMessagingResponseDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
