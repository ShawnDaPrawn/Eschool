// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'content_settings.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ContentSettings _$ContentSettingsFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'ContentSettings',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'transcription_enabled',
            'daily_transcription_seconds',
            'revision',
          ],
        );
        final val = ContentSettings(
          transcriptionEnabled: $checkedConvert(
            'transcription_enabled',
            (v) => v as bool,
          ),
          dailyTranscriptionSeconds: $checkedConvert(
            'daily_transcription_seconds',
            (v) => (v as num).toInt(),
          ),
          revision: $checkedConvert('revision', (v) => (v as num).toInt()),
        );
        return val;
      },
      fieldKeyMap: const {
        'transcriptionEnabled': 'transcription_enabled',
        'dailyTranscriptionSeconds': 'daily_transcription_seconds',
      },
    );

Map<String, dynamic> _$ContentSettingsToJson(ContentSettings instance) =>
    <String, dynamic>{
      'transcription_enabled': instance.transcriptionEnabled,
      'daily_transcription_seconds': instance.dailyTranscriptionSeconds,
      'revision': instance.revision,
    };
