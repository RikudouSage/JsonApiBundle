<?php

namespace Rikudou\JsonApiBundle\NameResolution;

interface ApiNameResolutionInterface
{
    /**
     * Transforms the class name to resource name.
     *
     * Example:
     *   Activity      -> activity
     *   ActivityState -> activity-state
     *
     * @param string $className
     *
     * @return string
     */
    public function getResourceName(string $className): string;

    /**
     * Transforms the class name to plural version of resource name.
     *
     * Example:
     *  Activity      -> activities
     *  ActivityState -> activity-states
     *
     * @param string $className
     *
     * @return string
     */
    public function getResourceNamePlural(string $className): string;

    /**
     * Transforms the property name to attribute name.
     *
     * Examples:
     *   myProperty  -> myProperty
     *   myProperty  -> my_property
     *   my_property -> myProperty
     *
     * @param string $propertyName
     *
     * @return string
     */
    public function getAttributeNameFromProperty(string $propertyName): string;

    /**
     * Transforms the method name to attribute name
     *
     * Examples:
     *   getMyProperty -> myProperty
     *   getMyProperty -> my_property
     *
     * @param string $methodName
     *
     * @return string
     */
    public function getAttributeNameFromMethod(string $methodName): string;

    /**
     * Returns getter name for the property.
     *
     * Examples:
     *   my_property -> getMyProperty
     *   myProperty -> getMyProperty
     *
     * @param string $propertyName
     *
     * @return string
     */
    public function getGetter(string $propertyName): string;

    /**
     * Returns setter name for the property.
     *
     * Examples:
     *   my_property -> setMyProperty
     *   myProperty -> setMyProperty
     *
     * @param string $propertyName
     *
     * @return string
     */
    public function getSetter(string $propertyName): string;

    /**
     * Returns the adder name for the property.
     *
     * Examples:
     *   my_objects -> addMyObject
     *   myObjects -> addMyObject
     *
     * @param string $propertyName
     *
     * @return string
     */
    public function getAdder(string $propertyName): string;

    /**
     * Returns the name of the method that removes item from collection for the property.
     *
     * Examples:
     *   my_objects -> removeMyObject
     *
     * @param string $propertyName
     *
     * @return string
     */
    public function getRemover(string $propertyName): string;

    /**
     * Returns the isser name for the property name.
     *
     * Examples:
     *   saved -> isSaved
     *
     * @param string $propertyName
     *
     * @return string
     */
    public function getIsser(string $propertyName): string;

    /**
     * Returns the hasser for the given property name.
     *
     * Examples:
     *   user -> hasUser
     *
     * @param string $propertyName
     *
     * @return string
     */
    public function getHasser(string $propertyName): string;
}
