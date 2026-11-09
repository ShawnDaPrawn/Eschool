// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'text_messaging_response_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TextMessagingResponseData _$TextMessagingResponseDataFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('TextMessagingResponseData', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['number', 'notices']);
  final val = TextMessagingResponseData(
    number: $checkedConvert(
      'number',
      (v) =>
          TextMessagingResponseDataNumber.fromJson(v as Map<String, dynamic>),
    ),
    notices: $checkedConvert(
      'notices',
      (v) => (v as List<dynamic>)
          .map(
            (e) => TextMessagingResponseDataNoticesInner.fromJson(
              e as Map<String, dynamic>,
            ),
          )
          .toList(),
    ),
  );
  return val;
});

Map<String, dynamic> _$TextMessagingResponseDataToJson(
  TextMessagingResponseData instance,
) => <String, dynamic>{
  'number': instance.number.toJson(),
  'notices': instance.notices.map((e) => e.toJson()).toList(),
};
