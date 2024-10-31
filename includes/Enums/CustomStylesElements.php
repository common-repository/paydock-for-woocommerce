<?php

namespace Paydock\Enums;

use Paydock\Abstracts\AbstractEnum;

class CustomStylesElements extends AbstractEnum {
	const INPUT = 'input';
	const LABEL = 'label';
	const TITLE = 'title';
	const DESCRIPTION = 'title_description';

	public static function getElements(): array {
		return array_map( function (self $element) {
			return $element->value;
		}, self::cases() );
	}

	public static function getElementFor( string $value ) {

		switch ( $value ) {
			case self::INPUT:
				$result = self::INPUT();
				break;
			case self::LABEL:
				$result = self::LABEL();
				break;
			case self::TITLE:
				$result = self::TITLE();
				break;
			case self::DESCRIPTION:
				$result = self::DESCRIPTION();
				break;
			default:
				$result = self::INPUT();
				break;
		}

		return $result;
	}

	public function getStyleKeys(): array {
		switch ( $this->value ) {
			case self::INPUT:
				$styles = CustomInputStyles::cases();
				break;
			case self::LABEL:
				$styles = CustomLabelStyles::cases();
				break;
			case self::TITLE:
				$styles = CustomTitleStyles::cases();
				break;
			case self::DESCRIPTION:
				$styles = CustomDescriptionStyles::cases();
				break;
			default:
				$styles = [];
				break;
		}

		return array_map( function ($element) {
			return $element->value;
		}, $styles );
	}
}
