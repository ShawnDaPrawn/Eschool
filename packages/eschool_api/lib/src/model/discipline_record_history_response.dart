//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/discipline_record_history.dart';
import 'package:json_annotation/json_annotation.dart';

part 'discipline_record_history_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DisciplineRecordHistoryResponse {
  /// Returns a new [DisciplineRecordHistoryResponse] instance.
  DisciplineRecordHistoryResponse({
    required this.error,

    required this.message,

    required this.code,

    required this.data,
  });

  @JsonKey(name: r'error', required: true, includeIfNull: false)
  final bool error;

  @JsonKey(name: r'message', required: true, includeIfNull: false)
  final String message;

  // minimum: 0
  @JsonKey(name: r'code', required: true, includeIfNull: false)
  final int code;

  @JsonKey(name: r'data', required: true, includeIfNull: false)
  final DisciplineRecordHistory data;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DisciplineRecordHistoryResponse &&
          other.error == error &&
          other.message == message &&
          other.code == code &&
          other.data == data;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + code.hashCode + data.hashCode;

  factory DisciplineRecordHistoryResponse.fromJson(Map<String, dynamic> json) =>
      _$DisciplineRecordHistoryResponseFromJson(json);

  Map<String, dynamic> toJson() =>
      _$DisciplineRecordHistoryResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
